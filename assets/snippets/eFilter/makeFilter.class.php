<?php
class makeFilter{

public $out = '';

public function __construct($modx, $params){
    $this->modx = $modx;
    $this->params = $params;
    $this->get = isset($_GET) ? $_GET : array();
    $this->tvs = $this->makeTVIds();
    $this->docid = $this->modx->documentIdentifier;
    //array [tv_id = param_id] = config_array (fltr_name, fltr_type, show_zagol, show_href, show_all, new_line)
    $this->add_config = $this->makeAddConfig();
}

public function run() {
    $this->tv_arr = $this->makeTVsInfo($this->tvs);
    $this->tv_arr = $this->makeTVsValues($this->tv_arr);
    $this->out = $this->render($this->tv_arr);
    return $this->out;
}

public function makeTVIds() {
    $tvs = isset($this->params['tvs']) ? explode(",", $this->params['tvs']) : array();
    foreach ($tvs as $k => $tv) {
        $tvs[$k] = (int)$tv;
    }
    return $tvs;
}

public function makeTVsInfo ($tvs) {
    $tmp = array();
    if (!empty($tvs)) {
        $q = $this->modx->db->query("SELECT id,name,caption,elements,category FROM " . $this->modx->getFullTableName('site_tmplvars') . " WHERE id IN (" . implode(',', $tvs) . ") ORDER BY rank ASC");
        while ($row = $this->modx->db->getRow($q)) {
            $tmp[$row['id']] = $row;
        }
    }
    return $tmp;
}

public function makeTVsValues($tv_arr) {
    foreach ($tv_arr as $id => $tv) {
        if (isset($tv['elements'])) {
            $elements = $tv['elements'];
            if (stripos($elements, '@EVAL') !== FALSE) {
                $values = str_replace('@EVAL', '', $elements);
                $values = trim($values);
                $values = str_replace('$modx', '$this->modx', $values);
                $values = eval($values);
                $tv_arr[$id]['values'] = $values;
            } else {
                $tv_arr[$id]['values'] = $elements;
            }
        }
    }
    return $tv_arr;
}

public function makeAddConfig() {
    $add_config = array();
    if (isset($this->params['add_config']) && !empty($this->params['add_config'])) {//если прислан уже готовый конфиг
        $add_config = $this->params['add_config'];
    } else {//todo: если не прислали, то попробовать сформировать из самого вызова

    }
    return $add_config;
}

public function getParam($param, $default = '1') {
    return isset($param) && !empty($param) ? $param : $default;
}

public function makeExistsTV($_DLjson, $id) {
    $_ids = array();
    $exist_tv = array();
    if ($_DLjson['total'] != 0) {
        foreach ($_DLjson['rows'] as $row) {
            $_ids[] = $row['id'];
        }
    }
    if (!empty($_ids)) {
        $tv_values = $this->modx->db->query("SELECT DISTINCT(value) FROM modx_site_tmplvar_contentvalues WHERE contentid IN (" . implode($_ids, ',') . ") AND tmplvarid = " . $id);
        while($r = $this->modx->db->getRow($tv_values)) {
            $_tmp = explode('||', $r['value']);
            foreach ($_tmp as $val) {
                $exist_tv[$val] = '1';
            }
        }
    }
    return $exist_tv;
}

public function render($tv_arr){
    $out = '';
    $i = 0;
    $filter = '';
    //возвращаем в формате json список подходящих документов из данного раздела
    $catalogJSON = $this->modx->getPlaceholder("catalogJSON");
    $_ = json_decode($catalogJSON, true);
    foreach ($tv_arr as $id => $tv) {
        $rows = '';
        $more = '';
        $type = isset($this->params['type']) ? $this->params['type'] : 'checkbox';
        $type = $this->getParam($this->add_config[$id]['fltr_type'], $type);
        $name = $tv['name'];
        //заголовок фильтра (либо задан либо имя TV
        $zagol = $this->getParam($this->add_config[$id]['fltr_name'], $tv['caption']);
        //показывать заголовок блока - 1=да
        $show_zagol = $this->getParam($this->add_config[$id]['show_zagol'], '1');
        //показывать варианты ссылками, 1=да
        $show_href = $this->getParam($this->add_config[$id]['show_href'], '1');
        //показывать все - если 1, то показывает все доступные варианты, если 0 - только те, которые принадлежат данному разделу
        $show_all = $this->getParam($this->add_config[$id]['show_all'], '1');
        //перенос строки clearfix для последующих фильтров
        $new_line = $this->getParam($this->add_config[$id]['new_line'], '0');
        //html-код переноса строки
        $new_line_html = $this->getParam($this->add_config[$id]['new_line_html'], '<div class="clearfix"></div>');
        $clear_line = $new_line == '1' ? $new_line_html : '';
        //только доступные значения ТВ
        $exist_tv = array();
        //количество видимых строк (чекбоксов в блоке, для списка чекбоксов предварительно поставить высоту и overflow:hidden)
        //добавляет к блоку строку из параметра &visible_row_text, на которую вешается скрипт раскрытия/скрытия блока (пишется отдельно) и класс hidden_rows к самому блоку
        $visible_row = !isset($this->params['visible_row']) ? false : '3';
        //строка, показывается при наличии скрытых строк в фильтре
        $visible_row_text = isset($this->params['visible_row_text']) ? $this->params['visible_row_text'] : '<div class="filter_more"><a href="#">Ещё...</a></div>';
        //количество видимых (остальные скрыты - присваиваем блоку класс hidden)
        //добавляет после всех блоков строку из параметра &visible_block_text, на которую можно повесить скрипт скрытия/показа скрываемых блоков
        $visible_block = !isset($this->params['visible_block']) ? false : (int)$this->params['visible_block'];
        //строка показывается после всех блоков фильтра (при наличии скрытых)
        $visible_block_text = isset($this->params['visible_block_text']) ? $this->params['visible_block_text'] : '<div class="filter_show_all"><span data-target-class="filter_block_inner.hidden">Показать все критерии</span></div>';

        switch ($type) {
            case 'checkbox':
                $exist_tv = $show_all == '1' ? array() : $this->makeExistsTV ($_, $id);
                $ignoreEmpty = '1';
                $outerTpl = isset($this->params['outerTpl']) ? $this->params['outerTpl'] : '
                    <div class="filter_block filter_block[+param_id+] bbx [+hidden+] [+active+]">
                        ' . ($show_zagol == '1' ? '<div class="filter_zagol bg_gray">[+zagol+]</div>' : '') . '
                        <div class="filter_list">
                            [+rows+]
                        </div>
                        [+more+]
                    </div>[+clear+]
                ';
                $rowTpl = isset($this->params['rowTpl']) ? $this->params['rowTpl'] : ($show_href == '1' ? '<div class="filter_row"><input type="checkbox" name="[+name+]" value="[+value+]" [+checked+]> <a href="[+url+]">[+label+]</a></div>' : '<div class="filter_row"><input type="checkbox" name="[+name+]" value="[+value+]" [+checked+]> [+label+]</div>');
                if (isset($tv['values'])) {
                    $tmp = explode('||', $tv['values']);
                    $j = 1;
                    foreach($tmp as $_tmp) {
                        $__tmp = explode('==', $_tmp);
                        $label = $__tmp[0];
                        $value = isset($__tmp[1]) ? $__tmp[1] : $__tmp[0];
                        if ($show_all != '1') {
                            if (isset($exist_tv[$value])) {
                                if ($ignoreEmpty != '1' || $label != '') {
                                    $url = (int)$value != 0 ? $this->modx->makeUrl((int)$value) : '';
                                    $rows .= $this->parseTpl (array('[+name+]','[+value+]','[+checked+]','[+label+]', '[+url+]'), array($name . '[]', $value, $this->getGet($name, $type, $value, '1'), $label, $url), $rowTpl);
                                }
                                $j++;
                            }
                        } else {
                            if ($ignoreEmpty != '1' || $label != '') {
                                $url = (int)$value != 0 ? $this->modx->makeUrl((int)$value) : '';
                                $rows .= $this->parseTpl (array('[+name+]','[+value+]','[+checked+]','[+label+]', '[+url+]'), array($name . '[]', $value, $this->getGet($name, $type, $value, '1'), $label, $url), $rowTpl);
                            }
                        }
                    }
                    if ($show_all == '1') {
                        $j = count($tmp);
                    }
                    if ($visible_row && $j > $visible_row) {
                        $more = '<div class="filter_more"><a href="#">Ещё...</a></div>';
                    }
                }
            break;
            case 'simplecheckbox':
                $ignoreEmpty = '1';
                $outerTpl = isset($this->params['outerTpl']) ? $this->params['outerTpl'] : '[+rows+][+clear+]';
                $rowTpl = isset($this->params['rowTpl']) ? $this->params['rowTpl'] : ($show_href == '1' ? '<div class="filter_row"><input type="checkbox" name="[+name+]" value="[+value+]" [+checked+]> <a href="[+url+]">[+label+]</a></div>' : '<div class="filter_row"><input type="checkbox" name="[+name+]" value="[+value+]" [+checked+]> [+label+]</div>');
                if (isset($tv['values'])) {
                    $tmp = explode('||', $tv['values']);
                    foreach($tmp as $_tmp) {
                        $__tmp = explode('==', $_tmp);
                        $label = $__tmp[0];
                        $value = isset($__tmp[1]) ? $__tmp[1] : $__tmp[0];
                        if ($ignoreEmpty != '1' || $label != '') {
                            $url = (int)$value != 0 ? $this->modx->makeUrl((int)$value) : '';
                            $rows .= $this->parseTpl (array('[+name+]','[+value+]','[+checked+]','[+label+]', '[+url+]'), array($name . '[]', $value, $this->getGet($name, $type, $value, '1'), $label, $url), $rowTpl);
                        }
                    }
                }
            break;
            case 'option':
                $ignoreEmpty = '1';
                $outerTpl = isset($this->params['outerTpl']) ? $this->params['outerTpl'] : '
                    <div class="filter_block filter_block[+param_id+] filter_block_select filter_block_select[+param_id+] bbx [+hidden+] [+active+]">
                        ' . ($show_zagol == '1' ? '<div class="filter_zagol filter_zagol_select">[+zagol+]</div>' : '') . '
                        <div class="filter_list filter_list_select">
                            <select name="[+name+]">
                                [+rows+]
                            </select>
                        </div>
                    </div>
                    [+clear+]
                ';
                $rowTpl = isset($this->params['rowTpl']) ? $this->params['rowTpl'] : '<option value="[+value+]" [+checked+]>[+label+]</option>';
                if (isset($tv['values'])) {
                    $tmp = explode('||', $tv['values']);
                    foreach($tmp as $_tmp) {
                        $__tmp = explode('==', $_tmp);
                        $label = $__tmp[0];
                        $value = isset($__tmp[1]) ? $__tmp[1] : $__tmp[0];
                        if ($ignoreEmpty != '1' || $label != '') {
                            $rows .= $this->parseTpl (array('[+name+]','[+value+]','[+checked+]','[+label+]'), array($name . '[]', $value, $this->getGet($name, $type, $value, '1'), $label), $rowTpl);
                        }
                    }
                }
            break;

            default:
            break;
        }
        if (!empty($rows)) {
            $hidden = ($visible_row && $i > $visible_row) ? ' hidden_rows' : '';
            $active = $i == 0 ? ' active' : '';
            $filter .= $this->parseTpl (array('[+zagol+]', '[+rows+]', '[+hidden+]', '[+active+]', '[+name+]', '[+more+]', '[+param_id+]', '[+clear+]'), array($zagol, $rows, $hidden, $active, $name, $more, $id, $clear_line), $outerTpl);
        }
        $i++;
    }
    if ($visible_block && $i > $visible_block) {
        $filter .= '<div class="filter_show_all"><span data-target-class="filter_block_inner.hidden">Показать все критерии</span></div>';
    }
    return $filter;
}

public function getGet($name, $action, $default = '', $int = '0') {
    $out = '';
    switch ($action) {
        case 'checkbox':
            if (isset($this->get[$name])) {
                if (is_scalar($this->get[$name])) {
                    $out .= $modx->db->escape($this->get[$name]) == $default ? ' checked="checked"' : '';
                } else if (is_array($this->get[$name])) {
                $out .= in_array($default, $this->get[$name]) ? ' checked="checked"' : '';
                } else {
        
                }
            }
            if ($this->docid == $default && $out == '') {
                $out .= ' checked="checked"';
            }
            break;
        case 'option':
            if (isset($this->get[$name])) {
                if (is_scalar($this->get[$name])) {
                    $out .= $this->modx->db->escape($this->get[$name]) == $default ? ' selected="selected"' : '';
                } else if (is_array($this->get[$name])) {
                    $out .= in_array($default, $this->get[$name]) ? ' selected="selected"' : '';
                } else {
    
                }
            }
            if ($this->docid == $default && $out == '') {
                $out .= ' selected="selected"';
            }
            break;
        default:
            if ($int == '1') {
                $out .= isset($this->get[$name]) ? (int)$this->get[$name] : $default;
            } else {
                $out .= isset($this->get[$name]) ? $this->modx->db->escape($this->get[$name]) : $default;
            }
        break;
    }
return $out;
}

public function parseTpl ($array1, $array2, $tpl) {
    return str_replace($array1, $array2, $tpl);
}

}//end class