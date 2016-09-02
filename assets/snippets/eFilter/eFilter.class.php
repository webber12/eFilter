<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
$output = "";

class eFilter {

//id TV в котором хранятся настройки для категории товара
public $param_tv_id = '';

//имя TV в котором хранятся настройки для категории товара
public $param_tv_name = '';

//исходные параметры фильтра из json-строки multiTV
public $filter_param = array();

//массив заданных фильтров по категориям filter_cat -> array (tv_id)
public $filter_cats = array();

//массив заданных фильтров tv_id -> array (fltr_type,fltr_name,fltr_many)
public $filters = array();

//массив id tv входящих в заданный фильтр 
public $filter_tvs = array();

//массив id TV, входящих в список вывода для DocLister
public $list_tv_ids = array();

//массив имен TV, входящих в список вывода для DocLister
public $list_tv_names = array();

//массив имен (caption) TV, входящих в список вывода для DocLister
public $list_tv_captions = array();

//массив имен (описаний caption) tv входящих в заданный фильтр
public $filter_names = array();

//массив имен (name) tv входящих в заданный фильтр id1=>name1;id2=>name2
public $filter_tv_names = array();

//строка id tv заданных фильтров
public $filter_tv_ids = '';

//все возможные значения до фильтрации tv_id =>array()
//Array ( [14] => Array ( [синий] => Array ( [0] => 1 [1] => 1 ) [желтый] => Array ( [0] => 1 ) [красный] => Array ( [0] => 1 ) ) [16] => Array ( [Коллекция 1] => Array ( [0] => 1 ) [Коллекция 2] => Array ( [0] => 1 ) ) [17] => Array ( [S] => Array ( [0] => 1 ) [M] => Array ( [0] => 1 ) ) )
//можно посчитать количество по каждому из фильтров
public $filter_values_full = array();

//оставшиеся возможные значения после фильтрации tv_id =>array()
//Array ( [14] => Array ( [синий] => Array ( [0] => 1 [1] => 1 ) [желтый] => Array ( [0] => 1 ) [красный] => Array ( [0] => 1 ) ) [16] => Array ( [Коллекция 1] => Array ( [0] => 1 ) [Коллекция 2] => Array ( [0] => 1 ) ) [17] => Array ( [S] => Array ( [0] => 1 ) [M] => Array ( [0] => 1 ) ) )
//можно посчитать количество по каждому из фильтров
public $filter_values = array();

//текущие значения фильтра для поиска tv_id =>array()
public $curr_filter_values = array();

//текущие значения фильтра для поиска из $_GET['f']
public $fp = array();

//показывать 0 или ничего не показывать
public $zero = '';

//список id, значения которых не нужно сортировать
public $nosort_tv_id = array();

//тип фильтра для DocLister. По умолчанию - tvd
public $dl_filter_type;

public function __construct($modx, $params)
{
    $this->modx = $modx;
    $this->params = $params;
    $this->param_tv_id = $this->params['param_tv_id'];
    $this->param_tv_id_simple = $this->params['param_tv_id_simple'];
    $this->param_tv_name = $this->getParamTvName();
    $this->param_tv_name_simple = $this->getParamTvName($this->param_tv_id_simple);
    $this->product_templates_id = $this->params['product_templates_id'];
    $this->product_templates_array = explode(',', $this->product_templates_id);
    $this->docid = isset($this->params['docid']) ? $this->params['docid'] : $this->modx->documentIdentifier;
    $this->cfg = (isset($this->params['cfg']) && $this->params['cfg'] != '') ? $this->params['cfg'] : 'default';
    $this->params['remove_disabled'] = isset($this->params['remove_disabled']) && $this->params['remove_disabled'] != '0' ? '1' : '0';
    $this->fp = isset($_GET) ? $_GET : array();
    $this->zero = isset($this->params['hide_zero']) ? '' : '0';
    $this->pattern_folder = (isset($this->params['pattern_folder']) && $this->params['pattern_folder'] != '') ? $this->params['pattern_folder'] : 'assets/images/pattern/';
    $this->nosort_tv_id = isset($this->params['nosort_tv_id']) ? explode(',', $this->params['nosort_tv_id']) : array();
    $this->dl_filter_type = isset($this->params['dl_filter_type']) ? $this->params['dl_filter_type'] : 'tvd';
    $this->prepareGetParams($this->fp);
}

public function getParamTvName($tv_id = '')
{
    $tv_id = !empty($tv_id) ? $tv_id : $this->param_tv_id;
    return $this->modx->db->getValue("SELECT `name` FROM " . $this->modx->getFullTableName('site_tmplvars') . " WHERE id = {$tv_id} LIMIT 0,1");
}

public function getFilterParam ($param_tv_name)
{
    $filter_param = array();
    $tv_config = isset ($this->params['tv_config']) ? $this->params['tv_config'] : '';
    if ($tv_config != '') {
        $filter_param = json_decode($tv_config, true);
    } else {
        $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid'=>$this->docid, 'tv'=>'1', 'field'=>$param_tv_name));
        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {//если задано для категории, ее и берем
            $filter_param = json_decode($param_tv_val, true);
        } else {//если не задано, идем к родителю
            $filter_param = $this->_getParentParam ($this->docid, $param_tv_name);
            /*$parent = $this->modx->db->getValue("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$this->docid} AND parent != 0 LIMIT 0,1");
            if ($parent) {
                $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid'=>$parent, 'tv'=>'1', 'field'=>$param_tv_name));
                if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {
                    $filter_param = json_decode($param_tv_val, true);
                } else {//если и у родителя нет, идет к дедушке
                    $parent2 = $this->modx->db->getValue("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$parent} AND parent != 0 LIMIT 0,1");
                    if ($parent2) {
                        $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid'=>$parent2, 'tv'=>'1', 'field'=>$param_tv_name));
                        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {
                            $filter_param = json_decode($param_tv_val, true);
                        }  else {//если и у дедушки нет, идет к прадедушке
                            $parent3 = $this->modx->db->getValue("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$parent2} AND parent != 0 LIMIT 0,1");
                            if ($parent3) {
                                $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid'=>$parent3, 'tv'=>'1', 'field'=>$param_tv_name));
                                if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {
                                    $filter_param = json_decode($param_tv_val, true);
                                } else {//если и у прадедушки нет, идет к прапрадедушке
                                    $parent4 = $this->modx->db->getValue("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$parent3} AND parent != 0 LIMIT 0,1");
                                    if ($parent4) {
                                        $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid'=>$parent4, 'tv'=>'1', 'field'=>$param_tv_name));
                                        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {
                                            $filter_param = json_decode($param_tv_val, true);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }*/
        }
    }
    return $filter_param;
}

public function _getParentParam ($docid, $param_tv_name) {
    $filter_param = array();
    $parent = $this->modx->db->getValue("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$docid} AND parent != 0 LIMIT 0,1");
    if ($parent) {
        $param_tv_val = $this->modx->runSnippet("DocInfo", array('docid' => $parent, 'tv' => '1', 'field' => $param_tv_name));
        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}' && $param_tv_val != '[]') {
            $filter_param = json_decode($param_tv_val, true);
        }  else {
            $filter_param = $this->_getParentParam ($parent, $param_tv_name);
        }
    }
    return $filter_param;
}

public function makeFilterArrays()
{
    foreach ($this->filter_param['fieldValue'] as $k => $v) {
        if ($v['fltr_yes'] == '1'){
            $this->filter_tvs[] = $v['param_id'];
            $this->filter_names[$v['fltr_name']] = $v['param_id'];
            $this->filter_cats[$v['cat_name']][$v['param_id']] = '1';
            $this->filters[$v['param_id']]['type'] = $v['fltr_type'];
            $this->filters[$v['param_id']]['name'] = $v['fltr_name'];
            $this->filters[$v['param_id']]['many'] = $v['fltr_many'];
        }
        if ($v['list_yes'] == '1'){
            $this->list_tv_ids[] = $v['param_id'];
        }
    }
}

public function getTVNames ($tv_ids = '', $field = 'name')
{
    $tv_names = array();
    if ($tv_ids != '') {
        $q = $this->modx->db->query("SELECT `a`.`id`, `a`.`".$field."` FROM " . $this->modx->getFullTableName('site_tmplvars') . " as `a`, " . $this->modx->getFullTableName('site_tmplvar_templates') . " as `b` WHERE `a`.`id` IN (" . $tv_ids . ") AND `a`.`id` = `b`.`tmplvarid` AND `b`.`templateid` IN(" . $this->product_templates_id . ") ORDER BY `b`.`rank` ASC, `a`.`$field` ASC");
        while ($row = $this->modx->db->getRow($q)){
            if (!isset($tv_names[$row['id']])) {
                $tv_names[$row['id']] = $row[$field];
            }
        }
    }
    return $tv_names;
}

public function parseTpl ($array1, $array2, $tpl)
{
    return str_replace($array1, $array2, $tpl);
}

public function renderFilterBlock ($filter_cats, $filter_values_full, $filter_values, $filters, $config = '')
{

    //подключаем файл конфигурации с шаблонами вывода формы
    if (is_file(dirname(__FILE__).'/config/config.'.$this->cfg.'.php')) {
        include(dirname(__FILE__).'/config/config.'.$this->cfg.'.php');
    } else {
        include(dirname(__FILE__).'/config/config.default.php');
    }
    
    $output = '';
    $fc = 0;
    foreach ($filter_cats as $cat_name => $tmp) {
        $output .= '<div class="eFiltr_cat eFiltr_cat' . $fc . '">';
        if (count($filter_cats) > 1) {$output .= $this->parseTpl(array('[+cat_name+]'), array($cat_name), $filterCatName);}
        $tv_elements = $this->getDefaultTVValues($tmp);
        foreach ($tmp as $tv_id => $tmp2) {
            if (isset($filter_values_full[$tv_id])) {
                if (in_array($tv_id, $this->nosort_tv_id)) {
                    $sort_tmp = array();
                    foreach($tv_elements[$tv_id] as $k => $v) {
                      if ( $filter_values_full[$tv_id][$k] ) {
                          $sort_tmp[$k] = $filter_values_full[$tv_id][$k];
                      }
                    }
                    $filter_values_full[$tv_id] = $sort_tmp;
                    unset($sort_tmp);
                } else {
                    uksort($filter_values_full[$tv_id], create_function('$a,$b', 'return is_numeric($a) && is_numeric($b) ? ($a-$b) : strcasecmp(strtolower($a), strtolower($b));'));
                }
                $wrapper = '';
                $count = '';
                //||Чекбокс==1||Список==2||Диапазон==3||Флажок==4||Мультиселект==5
                switch ($filters[$tv_id]['type']) {
                    case '1'://чекбоксы
                        $tplRow = $tplRowCheckbox;
                        $tplOuter = $tplOuterCheckbox;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'checked="checked" ';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;
                        
                    case '2': //селекты
                        $tplRow = $tplRowSelect;
                        $tplOuter = $tplOuterSelect;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'selected="selected" ';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;
                        
                    case '3': //диапазон
                        //исходя из запроса $_GET
                        $minval = '';
                        $maxval = '';
                        //смотрим мин. и макс. значения исходя из списка доступных contentid и запроса $_GET
                        //т.е. реальный доступный диапазон значений "от и до"
                        $minvalcurr = '';
                        $maxvalcurr = '';
                        
                        if (isset($this->curr_filter_values[$tv_id]['content_ids']) && $this->curr_filter_values[$tv_id]['content_ids'] != '') {
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS UNSIGNED) ) as min, MAX( CAST( `value` AS UNSIGNED) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN(" . $this->curr_filter_values[$tv_id]['content_ids'] . ") AND tmplvarid = {$tv_id}");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = $minmax['min'];
                            $maxvalcurr = $minmax['max'];
                        }
                        
                        $tplRow = $tplRowInterval;
                        $tplOuter = $tplOuterInterval;
                        $minvalcurr = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 && (int)$this->fp[$tv_id]['min'] >= (int)$minvalcurr ? (int)$this->fp[$tv_id]['min'] : $minvalcurr;
                        $maxvalcurr = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 && (int)$this->fp[$tv_id]['max'] <= (int)$maxvalcurr  ? (int)$this->fp[$tv_id]['max'] : $maxvalcurr;
                        $minval = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 ? (int)$this->fp[$tv_id]['min'] : $minval;
                        $maxval = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 ? (int)$this->fp[$tv_id]['max'] : $maxval;
                        $wrapper .= $this->parseTpl(
                            array('[+tv_id+]', '[+minval+]', '[+maxval+]', '[+minvalcurr+]', '[+maxvalcurr+]'),
                            array($tv_id, $minval, $maxval, $minvalcurr, $maxvalcurr),
                            $tplRow
                        );
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;

                    case '4': //radio
                        $tplRow = $tplRowRadio;
                        $tplOuter = $tplOuterRadio;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'checked="checked" ';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;

                    case '5': //мультиселекты
                        $tplRow = $tplRowMultySelect;
                        $tplOuter = $tplOuterMultySelect;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'selected="selected" ';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;

                    case '6': //слайдер-диапазон
                        //исходя из запроса $_GET
                        $minval = '';
                        $maxval = '';
                        //смотрим мин. и макс. значения исходя из списка доступных contentid и запроса $_GET
                        //т.е. реальный доступный диапазон значений "от и до"
                        $minvalcurr = '';
                        $maxvalcurr = '';
                        
                        if (isset($this->curr_filter_values[$tv_id]['content_ids']) && $this->curr_filter_values[$tv_id]['content_ids'] != '') {
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS UNSIGNED) ) as min, MAX( CAST( `value` AS UNSIGNED) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN(".$this->curr_filter_values[$tv_id]['content_ids'].") AND tmplvarid = {$tv_id}");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = $minmax['min'];
                            $maxvalcurr = $minmax['max'];
                        } else if (isset($this->content_ids_full) && $this->content_ids_full != '') {
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS UNSIGNED) ) as min, MAX( CAST( `value` AS UNSIGNED) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid = {$tv_id} AND contentid IN (" . $this->content_ids_full . ")");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = $minmax['min'];
                            $maxvalcurr = $minmax['max'];
                        } else { //фикс если ничего не выбрано - берем просто мин и макс цену
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS UNSIGNED) ) as min, MAX( CAST( `value` AS UNSIGNED) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid = {$tv_id}");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = $minmax['min'];
                            $maxvalcurr = $minmax['max'];
                        }
                        if ($minvalcurr == $maxvalcurr) { //фикс - если цена одинаковая то делаем мин.диапазон
                            $minvalcurr = $minvalcurr - 1;
                            $maxvalcurr = $maxvalcurr + 1;
                        }
                        
                        $tplRow = $tplRowSlider;
                        $tplOuter = $tplOuterSlider;
                        /*$minvalcurr = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 && (int)$this->fp[$tv_id]['min'] >= (int)$minvalcurr ? (int)$this->fp[$tv_id]['min'] : $minvalcurr;
                        $maxvalcurr = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 && (int)$this->fp[$tv_id]['max'] <= (int)$maxvalcurr  ? (int)$this->fp[$tv_id]['max'] : $maxvalcurr;*/
                        $minval = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 ? (int)$this->fp[$tv_id]['min'] : $minval;
                        $maxval = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 ? (int)$this->fp[$tv_id]['max'] : $maxval;
                        $wrapper .= $this->parseTpl(
                            array('[+tv_id+]', '[+minval+]', '[+maxval+]', '[+minvalcurr+]', '[+maxvalcurr+]'),
                            array($tv_id, $minval, $maxval, $minvalcurr, $maxvalcurr),
                            $tplRow
                        );
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;

                    case '7'://Цвет
                        $tplRow = $tplRowColors;
                        $tplOuter = $tplOuterColors;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            $label_selected = '';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'checked="checked" ';
                                    $label_selected = 'active';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+label_selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $label_selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;
                    
                    case '8'://Паттерны
                        $tplRow = $tplRowPattern;
                        $tplOuter = $tplOuterPattern;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            $label_selected = '';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'checked="checked" ';
                                    $label_selected = 'active';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+label_selected+]', '[+disabled+]', '[+count+]', '[+pattern_folder+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $label_selected, $disabled, $count, $this->pattern_folder),
                                    $tplRow
                                ) : '';
                            }
                        }
                        
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;
                    
                    default: //по умолчанию - чекбоксы
                        $tplRow = $tplRowCheckbox;
                        $tplOuter = $tplOuterCheckbox;
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = isset($tv_elements[$tv_id][$k]) ? $tv_elements[$tv_id][$k] : $k;
                            $selected = '  ';
                            if (isset ($this->fp[$tv_id])) {
                                $flag = false;
                                if (is_array($this->fp[$tv_id]) && in_array($k, $this->fp[$tv_id])) {
                                    $flag = true;
                                } else {
                                    $flag =  ($this->fp[$tv_id] == $k) ? true : false;
                                }
                                if ($flag) {
                                    $selected = 'checked="checked" ';
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['remove_disabled'] == '0' || $disabled == '') {
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+name+]', '[+wrapper+]'),
                            array($tv_id, $filters[$tv_id]['name'], $wrapper),
                            $tplOuter
                        );
                        break;
                }

            }
        }
        $fc++;
        $output .= '</div>';
    }
    $tpl = $tplFilterForm;
    $resetTpl = $tplFilterReset;
    $output = $output != '' ? $this->parseTpl(array('[+url+]', '[+wrapper+]'), array($this->modx->makeUrl($this->docid), $output), $tpl) : '';
    $output .= $output != '' ? $this->parseTpl(array('[+reset_url+]'), array($this->modx->makeUrl($this->modx->documentIdentifier)), $resetTpl) : '';
    return $output;
}

public function getFilterValues ($content_ids, $filter_tv_ids = '')
{
    $filter_values = array();
    if ($content_ids != '') {//берем только если есть какие-то документы
        $sql = "SELECT * FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN (" . $content_ids . ") " . ($filter_tv_ids != '' ? " AND tmplvarid IN (" . $filter_tv_ids . ")" : "");
        $q = $this->modx->db->query($sql);
        while ($row = $this->modx->db->getRow($q)) {
            if (strpos($row['value'], '||') === false) {
                $v = $row['value'];
                if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                    $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                } else {
                    $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                }
            } else {
                $tmp = explode("||", $row['value']);
                foreach ($tmp as $v) {
                    if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                        $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                    } else {
                        $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                    }
                }
            }
        }
    }
    return $filter_values;
}

public function getFilterFutureValues ($curr_filter_values, $filter_tv_ids = '')
{
    $filter_values = array();
    if (!empty($curr_filter_values)) {//берем только если есть какие-то документы
        foreach ($curr_filter_values as $tv_id => $v) {
            if (isset($v['content_ids']) && $v['content_ids'] != '') {
                $sql = "SELECT * FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN (" . $v['content_ids'] . ") " . ($filter_tv_ids != '' ? " AND tmplvarid ={$tv_id}" : "");
                $q = $this->modx->db->query($sql);
                while ($row = $this->modx->db->getRow($q)) {
                    if (strpos($row['value'], '||') === false) {
                        $v = $row['value'];
                        if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                            $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                        } else {
                            $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                        }
                    } else {
                        $tmp = explode("||", $row['value']);
                        foreach ($tmp as $v) {
                            if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                                $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                            } else {
                                $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                            }
                        }
                    }
                }
            }
        }
    }
    return $filter_values;
}


public function makeAllContentIDs ($DLparams)
{
    $this->content_ids = '';
    /*if (isset($input) && !empty($input) && isset($input['f'])) {//разбираем фильтры из строки GET и добавляем их в фильтр DocLister*/
    if (!empty($this->fp)) {//разбираем фильтры из строки GET и добавляем их в фильтр DocLister
        $f = $this->fp;
        $this->content_ids = '';
        if (is_array($f)) {
            $fltr = '';
            foreach ($f as $tvid => $v) {
                $tvid = (int)$tvid;
                $oper = 'eq';
                
                if (isset($v['min']) || isset($v['max'])) {//если параметр - диапазон
                    if (isset($v['min']) && (int)$v['min'] != 0 ) {
                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':egt:' . (int)$v['min'] . ';';
                    }
                    if (isset($v['max']) && (int)$v['max'] != 0 ) {
                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':elt:' . (int)$v['max'] . ';';
                    }
                } else {//если значение/значения, но не диапазон
                    if (is_array($v)) {
                        foreach($v as $k1 => $v1) {
                            if ($v1 == '0') {
                            unset($v[$k1]);
                            }
                        }
                        $val = implode(',', $v);
                        if (count($v) > 1) {
                            $oper = 'in';
                        }
                    } else {
                        $val = ($v == '0' || $v == '') ? '' : $v; 
                    }
                    if ($tvid != 0 && isset($this->filter_tv_names[$tvid]) && $val != '') {
                        if ($this->filters[$tvid]['many'] == '1') {
                            $oper = 'containsOne';
                        }
                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':' . $oper . ':' . $val . ';';
                    }
                }
            }
            $fltr = substr($fltr, 0 , -1);
            if ($fltr != '') {
                $fltr = 'AND(' . $fltr . ')';
                $DLparams['filters'] = $fltr;
                $_ = $this->modx->runSnippet("DocLister", $DLparams);
                $this->content_ids = $this->getListFromJson($_);
                //$this->content_ids = str_replace(' ', '', substr($this->content_ids, 0, -1));
            }
        }
    }
    return $this->content_ids;
}

public function makeCurrFilterValuesContentIDs ($DLparams)
{
    /*if (isset($input) && !empty($input) && isset($input['f'])) {//разбираем фильтры из строки GET и считаем возможные значения и количество для этих фильтров без учета одного из них (выбранного)*/
    if (!empty($this->fp)) {//разбираем фильтры из строки GET и считаем возможные значения и количество для этих фильтров без учета одного из них (выбранного)
        $f = $this->fp;
        if (is_array($f)) {
            foreach ($this->filter_tv_names as $fid =>$name) {
                $fltr = '';
                foreach ($f as $tvid => $v) {
                    if ($tvid != $fid) {
                        $tvid = (int)$tvid;
                        $oper = 'eq';
                        
                        if (isset($v['min']) || isset($v['max'])) { //если параметр - диапазон
                            if (isset($v['min']) && (int)$v['min'] != 0 ) {
                                $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':egt:' . (int)$v['min'].';';
                            }
                            if (isset($v['max']) && (int)$v['max'] != 0 ) {
                                $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':elt:' . (int)$v['max'].';';
                            }
                        } else {//если значение/значения, но не диапазон
                            if (is_array($v)) {
                                foreach($v as $k1 => $v1) {
                                    if ($v1 == '0') {
                                        unset($v[$k1]);
                                    }
                                }
                                $val = implode(',', $v);
                                if (count($v) > 1) {
                                    $oper = 'in';
                                }
                            } else {
                                $val = ($v == '0' || $v == '') ? '' : $v; 
                            }
                            if ($tvid != 0 && isset($this->filter_tv_names[$tvid]) && $val != '') {
                                if ($this->filters[$tvid]['many'] == '1') {$oper = 'containsOne';}
                                $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':' . $oper . ':' . $val.';';
                            }
                        }
                    }
                }
                $fltr = substr($fltr, 0 , -1);
                if ($fltr != '') {
                    $fltr = 'AND(' . $fltr . ')';
                    $DLparams['filters'] = $fltr;
                    //$tmp_content_ids = $this->modx->runSnippet("DocLister", $DLparams);
                    //$this->curr_filter_values[$fid]['content_ids'] = str_replace(' ', '', substr($tmp_content_ids, 0, -1));
                    $_ = $this->modx->runSnippet("DocLister", $DLparams);
                    $this->curr_filter_values[$fid]['content_ids'] = $this->getListFromJson($_);
                } else {
                    unset($DLparams['filters']);
                    //$tmp_content_ids = $this->modx->runSnippet("DocLister", $DLparams);
                    //$this->curr_filter_values[$fid]['content_ids'] = str_replace(' ', '', substr($tmp_content_ids, 0, -1));
                    $_ = $this->modx->runSnippet("DocLister", $DLparams);
                    $this->curr_filter_values[$fid]['content_ids'] = $this->getListFromJson($_);
                }
            }
        }
    }
    //return $this->curr_filter_values;
}

public function setPlaceholders ($array = array())
{
    if (!empty($array)) {
        foreach ($array as $k => $v) {
            $this->modx->setPlaceholder($k, $v);
        }
    }
}

public function prepareGetParams ($fp)
{
    $tmp = array();
    if (isset($fp['f']) && is_array($fp['f'])) {
        $tmp = $fp['f'];
    } else {
        //расшифровываем GET-строку формата f16=значение1,значение2&f17=значение3,значение4&f18=minmax~100,300 и преобразуем ее в обычный стандартный массив для обработки eFilter, 
        // array(
        //    "16" => array("значение1", "значение2"),
        //    "17" => array("значение3", "значение4"),
        //    "18" => array ("min" => "100", "max" => "300")
        //);
        //значения изначально должны быть url-кодированными, например через метод js encodeURIComponent
        foreach ($fp as $k => $v) {
            if (preg_match("/^f(\d+)/i", $k, $matches)) {
                $key = $matches[1];
                if (isset($matches[1]) && is_scalar($matches[1])) {
                    $minmax = strpos($v, 'minmax~');
                    if ($minmax !== false) {
                        $v = str_replace('minmax~', '', $v);
                    }
                    $tmp2 = explode(',', $v);
                    foreach ($tmp2 as $k2 => $v2) {
                        $tmp2[$k2] = urldecode($v2);
                    }
                    if ($minmax !== false) {
                        $tmp[$matches[1]]['min'] = isset($tmp2[0]) ? $tmp2[0] : '';
                        $tmp[$matches[1]]['max'] = isset($tmp2[1]) ? $tmp2[1] : '';
                    } else {
                        $tmp[$matches[1]] = $tmp2;
                    }
                }
            }
        }
    }
    $this->fp = $tmp;
}

public function prepareGetParamsOld ($fp)
{
    $out = array();
    if (is_scalar($fp) && $fp != '') {
        //расшифровываем GET-строку формата f=1~значение1,значение2||2~значение3,значение4||3~100,300~minmax и преобразуем ее в обычный массив $f, 
        //где 1,2,3 - id соответствующих тв для фильтрации, значение1,значение2 - из значения через запятую
        //значения изначально должны быть url-кодированными, например через метод js encodeURIComponent
        //на выходе получим нужный нам массив 
        //$f = array(
        //    "1" => array("значение1", "значение2"),
        //    "2" => array("значение3", "значение4"),
        //    "3" => array ("min" => "100", "max" => "300")
        //);
        $fp = urldecode($fp);
        $tmp = explode("||", $fp);
        foreach ($tmp as $v) {
            $tmp2 = explode("~", $v);
            $tmp3 = isset($tmp2[1]) && $tmp2[1] != '' ? explode(",", $tmp2[1]) : array();
            $tv_id = (int)$tmp2[0];
            if (isset($tmp2[2]) && $tmp2[2] == 'minmax') {
                $out['f'][$tv_id]['min'] = $tmp3[0];
                $out['f'][$tv_id]['max'] = ($tmp3[1] != '' ? $tmp3[1] : '');
            } else {
                $out['f'][$tv_id] = $tmp3;
            }
        }
        if (!empty($out['f'])) {
            $this->fp = $out['f'];
        } else {
            $this->fp = array();
        }
    } else {
        $this->fp = $fp;
    }
}

public function getDefaultTVValues($array = array())
{
    $out = array();
    $tvs = implode(",", array_keys($array));
    if ($tvs != '') {
        $elements = $this->getTVNames($tv_ids = $tvs, $field = 'elements');
        foreach ($elements as $tv_id => $element) {
            if (stristr($element, "@EVAL")) {
                $element = trim(substr($element, 6));
                $element = str_replace("\$modx->", "\$this->modx->", $element);
                $element = eval($element);
            }
            if ($element != '') {
                $tmp = explode("||", $element);
                foreach ($tmp as $v) {
                    $tmp2 = explode("==", $v);
                    $key = isset($tmp2[1]) && $tmp2[1] != '' ? $tmp2[1] : $tmp2[0];
                    $value = $tmp2[0];
                    if ($key != '') {
                        $out[$tv_id][$key] = $value;
                    }
                }
            }
        }
    }
    $this->modx->ef_elements_name = $out;
    return $out;
}

public function getListFromJson($json = '', $field = 'id', $separator = ',')
{
    $out = '';
    $_ = array();
    if (!empty($json)) {
        $tmp = json_decode($json, true);
        if (!empty($tmp) && isset($tmp['rows'])) {
            foreach ($tmp['rows'] as $row) {
                $_[] = $row[$field];
            }
        }
        $out = implode($separator, $_);
    }
    return $out;
}

}
