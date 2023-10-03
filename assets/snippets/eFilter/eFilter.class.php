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
public $nosort_tv_id = [];

//список id tv, вывод которых нужно сортировать по количеству элементов
public $sort_by_count_tv_id = [];

//тип фильтра для DocLister. По умолчанию - tvd
public $dl_filter_type;

//id tv, с помощью которого товары привязываются к категориям с помощью плагина tagSaver
public $tv_category_tag = 0;

//все продукты категории с учетом тегованных
public $categoryAllProducts = false;

//имя тв seocategory
public $seocategorytv = 'seocategory';

public $content_ids_full = [];
public $content_ids = [];
public $content_ids_cnt;
public $content_ids_cnt_ending;

public $modx = null;
public $params = [];
protected $product_templates_id;
protected $product_templates_array = [];
public $docid;
public $cfg;
protected $pattern_folder;
public $endings = [];
public $cntTpl;
public $active_block_class;
public $hideEmptyBlock;
public $commaAsSeparator;
public $changeInterval;
public $common_filters;
public $common_filter_cats;
public $common_filter_names;
public $common_filter_tvs;
public $list_tv_elements;


public function __construct($modx, $params)
{
    $this->modx = $modx;
    $this->params = $params;
    $this->param_tv_id = $this->params['param_tv_id'];
    //$this->param_tv_id_simple = $this->params['param_tv_id_simple'];
    $this->tv_category_tag = isset($this->params['tv_category_tag']) && (int)$this->params['tv_category_tag'] > 0 ? (int)$this->params['tv_category_tag'] : 0;
    $this->param_tv_name = $this->getParamTvName();
    //$this->param_tv_name_simple = $this->getParamTvName($this->param_tv_id_simple);
    $this->product_templates_id = $this->params['product_templates_id'];
    $this->product_templates_array = explode(',', $this->product_templates_id);
    $this->docid = isset($this->params['docid']) ? $this->params['docid'] : $this->modx->documentIdentifier;
    $this->cfg = (isset($this->params['cfg']) && $this->params['cfg'] != '') ? $this->params['cfg'] : 'default';
    $this->params['removeDisabled'] = isset($this->params['removeDisabled']) && $this->params['removeDisabled'] != '0' ? '1' : '0';
    $this->params['btnText'] = isset($this->params['btnText']) && $this->params['btnText'] != '' ? $this->params['btnText'] : 'Найти';
    $this->params['formMethod'] = 'get';
    $this->zero = isset($this->params['hideZero']) ? '' : '0';
    $this->pattern_folder = (isset($this->params['pattern_folder']) && $this->params['pattern_folder'] != '') ? $this->params['pattern_folder'] : 'assets/images/pattern/';
    $this->nosort_tv_id = isset($this->params['nosortTvId']) ? explode(',', $this->params['nosortTvId']) : [];
    $this->sort_by_count_tv_id = isset($this->params['sortByCountTvId']) ? explode(',', $this->params['sortByCountTvId']) : [];
    $this->dl_filter_type = isset($this->params['DLFilterType']) ? $this->params['DLFilterType'] : 'tvd';
    $this->getFP ();
    $this->prepareGetParams($this->fp);
    $this->endings = isset($this->params['endings']) && $this->params['endings'] != '' ? explode(',', $this->params['endings']) : array('товар', 'товара', 'товаров');
    $this->cntTpl = isset($this->params['cntTpl']) && $this->params['cntTpl'] != '' ? $this->params['cntTpl'] : 'Найдено: [+cnt+] [+ending+]';
    $this->active_block_class = isset($this->params['activeBlockClass']) ? $this->params['activeBlockClass'] : ' active ';
    $this->hideEmptyBlock = isset($this->params['hideEmptyBlock']) ? true : false;
    $this->setCommaAsSeparator();
    if (!empty($this->params['seocategorytv'])) {
        $this->seocategorytv = $this->params['seocategorytv'];
    }
    $this->changeInterval = isset($this->params['changeInterval']);
}

public function getParamTvName($tv_id = '')
{
    $tv_id = !empty($tv_id) ? $tv_id : $this->param_tv_id;
    return $this->modx->db->getValue($this->modx->db->query("SELECT `name` FROM " . $this->modx->getFullTableName('site_tmplvars') . " WHERE id = {$tv_id} LIMIT 0,1"));
}

public function getFilterParam ($param_tv_name, $docid = 0)
{
    if (!$docid) {
        $docid = $this->docid;
    }
    $filter_param = array();
    $tv_config = isset ($this->params['tvConfig']) ? $this->params['tvConfig'] : '';
    if ($tv_config != '') {
        $filter_param = json_decode($tv_config, true);
    } else {
        $tv = $this->modx->getTemplateVar($param_tv_name, '*', $docid);
        if($tv === false && !empty($this->params['showNoPublish'])) {
            $tv = $this->modx->getTemplateVar($param_tv_name, '*', $docid, 0);
        }
        $param_tv_val = $tv['value'] != '' ? $tv['value'] : ($tv['defaultText'] ?? '');
        //$param_tv_val = $this->modx->runSnippet("DocInfo", array('docid' => $docid, 'tv' => '1', 'field' => $param_tv_name));
        if ($param_tv_val != '' && $param_tv_val != '[]' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {//если задано для категории, ее и берем
            $filter_param = json_decode($param_tv_val, true);
        } else {//если не задано, идем к родителю
            $filter_param = $this->_getParentParam ($docid, $param_tv_name);
        }
    }
    return $filter_param;
}

public function _getParentParam ($docid, $param_tv_name) {
    $filter_param = array();
    $parent = $this->modx->db->getValue($this->modx->db->query("SELECT parent FROM " . $this->modx->getFullTableName('site_content') . " WHERE id = {$docid} /*AND parent != 0*/ LIMIT 0,1"));
    if ($parent || $parent == '0') {
        $tv = $this->modx->getTemplateVar($param_tv_name, '*', $docid);
        $param_tv_val = !empty($tv['value']) ? $tv['value'] : ($tv['defaultText'] ?? '');
        //$param_tv_val = $this->modx->runSnippet("DocInfo", array('docid' => $parent, 'tv' => '1', 'field' => $param_tv_name));
        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}' && $param_tv_val != '[]') {
            $filter_param = json_decode($param_tv_val, true);
        }  else {
            if ($parent) {
                $filter_param = $this->_getParentParam ($parent, $param_tv_name);
            }
        }
    }
    return $filter_param;
}

public function makeFilterArrays()
{
    $this->common_filter_tvs = $this->common_filter_names = $this->common_filter_cats = $this->common_filters = array();
    foreach ($this->filter_param['fieldValue'] as $k => $v) {
        if ($v['fltr_yes'] == '1'){
            $this->filter_tvs[] = $v['param_id'];
            $this->filter_names[$v['fltr_name']] = $v['param_id'];
            $this->filter_cats[$v['cat_name']][$v['param_id']] = '1';
            $this->filters[$v['param_id']]['type'] = $v['fltr_type'];
            $this->filters[$v['param_id']]['name'] = $v['fltr_name'];
            $this->filters[$v['param_id']]['many'] = $v['fltr_many'];
            $this->filters[$v['param_id']]['href'] = $v['fltr_href'];
        }
        if ($v['list_yes'] == '1'){
            $this->list_tv_ids[] = $v['param_id'];
        }
        $this->common_filter_tvs[] = $v['param_id'];
        $this->common_filter_names[$v['fltr_name']] = $v['param_id'];
        $this->common_filter_cats[$v['cat_name']][$v['param_id']] = '1';
        $this->common_filters[$v['param_id']]['type'] = $v['fltr_type'];
        $this->common_filters[$v['param_id']]['name'] = $v['fltr_name'];
        $this->common_filters[$v['param_id']]['many'] = $v['fltr_many'];
        $this->common_filters[$v['param_id']]['href'] = $v['fltr_href'];
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
    $categoryWrapper = '';
    $isEmpty = true;
    foreach ($filter_cats as $cat_name => $tmp) {
        //$output .= '<div class="eFiltr_cat eFiltr_cat' . $fc . ' ' . $filterCatClass . '">';
        //if (count($filter_cats) > 1) {$output .= $this->parseTpl(array('[+cat_name+]'), array($cat_name), $filterCatName);}
        $output = '';
        $tv_elements = $this->getDefaultTVValues($tmp);
        $tv_types = $this->getTVNames(implode(',', array_keys($tmp)), 'type');
        $tv_captions = $this->getTVNames(implode(',', array_keys($tmp)), 'caption');
        foreach ($tmp as $tv_id => $tmp2) {
            $filters[$tv_id]['name'] = $filters[$tv_id]['name'] ?: ($tv_captions[$tv_id] ?: '');
            $filters[$tv_id]['name'] = $this->translate($filters[$tv_id]['name']);
            if (isset($filter_values_full[$tv_id])) {
                if (!empty($tv_types) && $tv_types[$tv_id] == 'custom_tv:selector') {
                    $selectorDLParams = [
                        'documents' => implode(",", array_keys($filter_values_full[$tv_id])),
                        'returnDLObject' => 1,
                        'selectFields' => 'c.id,c.pagetitle',
                        'orderBy' => 'menuindex ASC',
                        'makeUrl' => 0,
                    ];
                    if(!empty($this->params['blang'])) {
                        $selectorDLParams['controller'] = 'lang_content';
                    }
                    $selector_elements = $this->modx->runSnippet("DocLister", $selectorDLParams)->getDocs();
                    foreach($selector_elements as $row) {
                        $tv_elements[$tv_id][ $row['id'] ] = $row['pagetitle'];
                    }
                }
                if (in_array($tv_id, $this->sort_by_count_tv_id) || (isset($this->sort_by_count_tv_id[0]) && $this->sort_by_count_tv_id[0] == 'all')) {
                    //сортируем все возможные значения по максимально возможному количеству
                    uasort($filter_values_full[$tv_id], function ($a, $b) {
                        if ($a['count'] == $b['count']) return 0;
                        return (int)$a['count'] > (int)$b['count'] ? -1 : 1;
                    });
                    //если какие-то значения выбраны и есть динамическая сортировка,
                    //применяем сортировку по данным количествам
                    if(!empty($filter_values[$tv_id]) && !empty($this->params['sortByCountDynamic'])) {
                        uasort($filter_values[$tv_id], function( $a,$b ) {
                            if($a['count'] == $b['count']) return 0;
                            return (int)$a['count'] > (int)$b['count'] ? -1 : 1;
                        });
                        $tmp_sort = [];
                        //сначала собираем те, что есть в $filter_values[$tv_id] в порядке их следования
                        foreach($filter_values[$tv_id] as $k => $v) {
                            if(isset($filter_values_full[$tv_id][$k])) {
                                $tmp_sort[$k] = $v;
                            }
                        }
                        //затем все остальные из исходных значений в порядке их следования
                        foreach($filter_values_full[$tv_id] as $k => $v) {
                            if(!isset($filter_values[$tv_id][$k])) {
                                $tmp_sort[$k] = $v;
                            }
                        }
                        $filter_values_full[$tv_id] = $tmp_sort;
                    }
                } else if (in_array($tv_id, $this->nosort_tv_id) || (isset($this->nosort_tv_id[0]) && $this->nosort_tv_id[0] == 'all')) {
                    $sort_tmp = array();
                    foreach($tv_elements[$tv_id] as $k => $v) {
                      if ( $filter_values_full[$tv_id][$k] ) {
                          $sort_tmp[$k] = $filter_values_full[$tv_id][$k];
                      }
                    }
                    $filter_values_full[$tv_id] = $sort_tmp;
                    unset($sort_tmp);
                } else {
                    uksort($filter_values_full[$tv_id], function($a, $b) {
                            return is_numeric($a) && is_numeric($b) ? ($a < $b ? -1 : ($a > $b ? 1 : 0)) : strcasecmp(strtolower($a), strtolower($b));
                        }
                    );
                }
                $wrapper = '';
                $count = '';
                //||Чекбокс==1||Список==2||Диапазон==3||Флажок==4||Мультиселект==5
                if(
                    (empty($filters[$tv_id]['type']) || in_array($filters[$tv_id]['type'], [ 1, 2, 4, 5, 7, 8])) &&
                    count($filter_values_full[$tv_id]) < 2 &&
                    !empty($this->params['hideSingleVariant'])) {
                    continue;
                }
                switch ($filters[$tv_id]['type']) {
                    case '1'://чекбоксы
                        $tplRow = $tplRowCheckbox;
                        $tplOuter = $tplOuterCheckbox;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
                            if ($filters[$tv_id]['href'] == '1' && is_int($k)) {
                                $tv_val_name = '<a href="' . $this->modx->makeUrl($k) . '">' . $tv_val_name . '</a>';
                            }
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= ($k != '' || ($k == '0' && isset($this->params['allowZero']))) ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '2': //селекты
                        $tplRow = $tplRowSelect;
                        $tplOuter = $tplOuterSelect;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= ($k != '' || ($k == '0' && isset($this->params['allowZero']))) ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
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
                        $active_block_class = '';

                        if (isset($this->curr_filter_values[$tv_id]['content_ids']) && $this->curr_filter_values[$tv_id]['content_ids'] != '') {
                            $content_ids = $this->curr_filter_values[$tv_id]['content_ids'] == 'all' ? $this->content_ids : $this->curr_filter_values[$tv_id]['content_ids'];
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS UNSIGNED) ) as min, MAX( CAST( `value` AS UNSIGNED) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN(" . $content_ids . ") AND tmplvarid = {$tv_id}");
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
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '4': //radio
                        $tplRow = $tplRowRadio;
                        $tplOuter = $tplOuterRadio;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
                            if ($filters[$tv_id]['href'] == '1' && is_int($k)) {
                                $tv_val_name = '<a href="' . $this->modx->makeUrl($k) . '">' . $tv_val_name . '</a>';
                            }
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= ($k != '' || ($k == '0' && isset($this->params['allowZero']))) ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '5': //мультиселекты
                        $tplRow = $tplRowMultySelect;
                        $tplOuter = $tplOuterMultySelect;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count = (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $wrapper .= ($k != '' || ($k == '0' && isset($this->params['allowZero']))) ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
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
                        $active_block_class = '';

                        if (isset($this->curr_filter_values[$tv_id]['content_ids']) && $this->curr_filter_values[$tv_id]['content_ids'] != '' && $this->changeInterval) {
                            $content_ids = $this->curr_filter_values[$tv_id]['content_ids'] == 'all' ? $this->content_ids : $this->curr_filter_values[$tv_id]['content_ids'];
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS DECIMAL(14, 2)) ) as min, MAX( CAST( `value` AS DECIMAL(14, 2)) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN(" . $content_ids . ") AND tmplvarid = {$tv_id}");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = floor($minmax['min']);
                            $maxvalcurr = ceil($minmax['max']);
                        } else if (isset($this->content_ids_full) && $this->content_ids_full != '') {
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS DECIMAL(14, 2)) ) as min, MAX( CAST( `value` AS DECIMAL(14, 2)) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid = {$tv_id} AND contentid IN (" . $this->content_ids_full . ")");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = floor($minmax['min']);
                            $maxvalcurr = ceil($minmax['max']);
                        } else { //фикс если ничего не выбрано - берем просто мин и макс цену
                            $q = $this->modx->db->query("SELECT MIN( CAST( `value` AS DECIMAL(14, 2)) ) as min, MAX( CAST( `value` AS DECIMAL(14, 2)) ) as max FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid = {$tv_id}");
                            $minmax = $this->modx->db->getRow($q);
                            $minvalcurr = floor($minmax['min']);
                            $maxvalcurr = ceil($minmax['max']);
                        }
                        if ($minvalcurr == $maxvalcurr) { //фикс - если цена одинаковая то делаем мин.диапазон
                            $minvalcurr = $minvalcurr - 1;
                            $maxvalcurr = $maxvalcurr + 1;
                        }
                        $maxvalcurr = $maxvalcurr != '' ? ceil($maxvalcurr) : '';

                        if(empty($minval)) { $minval = $minvalcurr; }
                        if(empty($maxval)) { $maxval = $maxvalcurr; }

                        $tplRow = $tplRowSlider;
                        $tplOuter = $tplOuterSlider;
                        /*$minvalcurr = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 && (int)$this->fp[$tv_id]['min'] >= (int)$minvalcurr ? (int)$this->fp[$tv_id]['min'] : $minvalcurr;
                        $maxvalcurr = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 && (int)$this->fp[$tv_id]['max'] <= (int)$maxvalcurr  ? (int)$this->fp[$tv_id]['max'] : $maxvalcurr;*/
                        $minval = isset($this->fp[$tv_id]['min']) && (int)$this->fp[$tv_id]['min'] != 0 ? (int)$this->fp[$tv_id]['min'] : $minval;
                        $maxval = isset($this->fp[$tv_id]['max']) && (int)$this->fp[$tv_id]['max'] != 0 ? (int)$this->fp[$tv_id]['max'] : $maxval;
                        $maxval = $maxval != '' ? ceil($maxval) : '';
                        $wrapper .= $this->parseTpl(
                            array('[+tv_id+]', '[+minval+]', '[+maxval+]', '[+minvalcurr+]', '[+maxvalcurr+]'),
                            array($tv_id, $minval, $maxval, $minvalcurr, $maxvalcurr),
                            $tplRow
                        );
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '7'://Цвет
                        $tplRow = $tplRowColors;
                        $tplOuter = $tplOuterColors;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
                            if ($filters[$tv_id]['href'] == '1' && is_int($k)) {
                                $tv_val_name = '<a href="' . $this->modx->makeUrl($k) . '">' . $tv_val_name . '</a>';
                            }
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+label_selected+]', '[+disabled+]', '[+count+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $label_selected, $disabled, $count, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '8'://Паттерны
                        $tplRow = $tplRowPattern;
                        $tplOuter = $tplOuterPattern;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
                            if ($filters[$tv_id]['href'] == '1' && is_int($k)) {
                                $tv_val_name = '<a href="' . $this->modx->makeUrl($k) . '">' . $tv_val_name . '</a>';
                            }
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+label_selected+]', '[+disabled+]', '[+count+]', '[+pattern_folder+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $label_selected, $disabled, $count, $this->pattern_folder, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    case '9'://одиночный чекбокс
                        $tplRow = $tplRowSingleCheckbox;
                        $tplOuter = $tplOuterSingleCheckbox;
                        $active_block_class = '';
                        $selected = '';
                        $count = 0;
                        if(empty($this->fp)) {
                            foreach ($filter_values_full[$tv_id] as $k => $v) {
                                $count += $v['count'] ?? 0;
                            }
                        } else {
                            foreach ($filter_values[$tv_id] as $k => $v) {
                                $count += $v['count'] ?? 0;
                            }
                        }
                        if (!empty($this->fp[$tv_id])) {
                            $selected = 'checked="checked" ';
                            $active_block_class = $this->active_block_class;
                        }
                        $disabled = empty($count) ? 'disabled' : '';
                        $wrapper = $this->parseTpl(
                            array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]', '[+iteration+]', '[+block_name+]'),
                            array($tv_id, 1, $this->params['singleCheckboxTitle'] ?? 'есть', $selected, $disabled, $count, 1, $filters[$tv_id]['name']),
                            $tplRow
                        );
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;

                    default: //по умолчанию - чекбоксы
                        $tplRow = $tplRowCheckbox;
                        $tplOuter = $tplOuterCheckbox;
                        $i = 0;
                        $active_block_class = '';
                        foreach ($filter_values_full[$tv_id] as $k => $v) {
                            $tv_val_name = $this->translate($tv_elements[$tv_id][$k] ?? $k);
                            if ($filters[$tv_id]['href'] == '1' && is_int($k)) {
                                $tv_val_name = '<a href="' . $this->modx->makeUrl($k) . '">' . $tv_val_name . '</a>';
                            }
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
                                    $active_block_class = $this->active_block_class;
                                }
                            }
                            $disabled = (!empty($filter_values) && !isset($filter_values[$tv_id][$k]) ? 'disabled' : '');
                            if ($disabled == '') {
                                $count =  (isset($filter_values[$tv_id][$k]['count']) ? $filter_values[$tv_id][$k]['count'] : $filter_values_full[$tv_id][$k]['count']);
                            } else {
                                $count = $this->zero;
                            }
                            if ($this->params['removeDisabled'] == '0' || $disabled == '') {
                                $i++;
                                $wrapper .= $k != '' ? $this->parseTpl(
                                    array('[+tv_id+]', '[+value+]', '[+name+]', '[+selected+]', '[+disabled+]', '[+count+]', '[+iteration+]'),
                                    array($tv_id, $k, $tv_val_name, $selected, $disabled, $count, $i),
                                    $tplRow
                                ) : '';
                            }
                        }
                        if ($this->hideEmptyBlock && $wrapper == '') break;
                        $output .= $this->parseTpl(
                            array('[+tv_id+]', '[+tv_name+]', '[+name+]', '[+wrapper+]', '[+active_block_class+]'),
                            array($tv_id, $this->filter_tv_names[$tv_id] ?? '', $filters[$tv_id]['name'], $wrapper, $active_block_class),
                            $tplOuter
                        );
                        break;
                }

            }
        }
        if ($output != '') {//есть, как минимум, одна непустая категория, т.е. фильтр надо выводить
            $isEmpty = false;
        }
        $categoryWrapper .= $this->parseTpl(
            array('[+cat_name+]', '[+iteration+]', '[+wrapper+]'),
            array($this->translate($cat_name), $fc, $output),
            $tplOuterCategory
        );
        $fc++;
        //$output .= '</div>';
    }
    $output = $categoryWrapper;
    $tpl = $tplFilterForm;
    $resetTpl = $tplFilterReset;
    $tmp = explode('?', $_SERVER['REQUEST_URI']);
    if (!empty($this->params['submitPage']) && is_numeric($this->params['submitPage'])) {
        $form_url = $this->modx->makeUrl($this->params['submitPage']);
    } else {
        $form_url = (isset($tmp[0]) && !empty($tmp[0]) && !isset($this->params['submitDocPage'])) ? $tmp[0] : $this->modx->makeUrl($this->docid);
    }
    $form_result_cnt = isset($this->content_ids_cnt) && $this->content_ids_cnt != '' ? $this->parseTpl(array('[+cnt+]', '[+ending+]'), array($this->content_ids_cnt, $this->content_ids_cnt_ending), $this->cntTpl) : '';
    $output = !$isEmpty ? $this->parseTpl(array('[+url+]', '[+wrapper+]', '[+btn_text+]', '[+form_result_cnt+]', '[+form_method+]'), array($form_url, $output, $this->params['btnText'], $form_result_cnt, $this->params['formMethod']), $tpl) : '';
    $output .= !$isEmpty ? $this->parseTpl(array('[+reset_url+]'), array($form_url), $resetTpl) : '';
    return $output;
}

public function getFilterValues ($content_ids, $filter_tv_ids = '')
{
    $filter_values = array();
    if ($content_ids != '') {//берем только если есть какие-то документы
        $sql = "SELECT * FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN (" . $content_ids . ") " . ($filter_tv_ids != '' ? " AND tmplvarid IN (" . $filter_tv_ids . ")" : "");
        $q = $this->modx->db->query($sql);
        while ($row = $this->modx->db->getRow($q)) {
            if ($this->commaAsSeparator) {
                if ($this->commaAsSeparator === true || (is_array($this->commaAsSeparator) && in_array($row['tmplvarid'], $this->commaAsSeparator))) {
                    $row['value'] = str_replace(',', '||', $row['value']);
                }
            }
            if (strpos($row['value'], '||') === false) {
                $v = $row['value'];
                if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                    $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                } else {
                    $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                }
            } else {
                $tmp = array_map('trim', explode("||", $row['value']));
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
                $content_ids = $v['content_ids'] == 'all' ? $this->content_ids : $v['content_ids'];
                $sql = "SELECT * FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE contentid IN (" . $content_ids . ") " . ($filter_tv_ids != '' ? " AND tmplvarid ={$tv_id}" : "");
                $q = $this->modx->db->query($sql);
                while ($row = $this->modx->db->getRow($q)) {
                    if ($this->commaAsSeparator) {
                        if ($this->commaAsSeparator === true || (is_array($this->commaAsSeparator) && in_array($row['tmplvarid'], $this->commaAsSeparator))) {
                            $row['value'] = str_replace(',', '||', $row['value']);
                        }
                    }
                    if (strpos($row['value'], '||') === false) {
                        $v = $row['value'];
                        if (isset($filter_values[$row['tmplvarid']][$v]['count'])) {
                            $filter_values[$row['tmplvarid']][$v]['count'] += 1;
                        } else {
                            $filter_values[$row['tmplvarid']][$v]['count'] = 1;
                        }
                    } else {
                        $tmp = array_map('trim', explode("||", $row['value']));
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

                switch(true) {

                    case $this->filters[$tvid]['type'] == '9':
                        //одиночный чекбокс
                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':isnotnull:;';
                        break;

                    case (isset($v['min']) || isset($v['max'])):
                        if (isset($v['min']) && (int)$v['min'] != 0 ) {
                            $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':egt:' . (int)$v['min'] . ';';
                        }
                        if (isset($v['max']) && (int)$v['max'] != 0 ) {
                            $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':elt:' . (int)$v['max'] . ';';
                        }
                        break;

                    default:
                        if (is_array($v)) {
                            if (!isset($this->params['allowZero'])) {
                                foreach($v as $k1 => $v1) {
                                    if ($v1 == '0') {
                                        unset($v[$k1]);
                                    }
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
                                if (isset($this->params['useRegexp'])) {
                                    $oper = 'regexp';
                                    $val = '[[:<:]]' . str_replace(array(',', '||'), '[[:>:]]|[[:<:]]', $val) . '[[:>:]]';
                                } else {
                                    $oper = 'containsOne';
                                }
                            }
                            $val = str_replace(array('(', ')'), array('\(', '\)'), $val);
                            $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':' . $oper . ':' . $val . ';';
                        }
                        break;
                }
            }
            $fltr = substr($fltr, 0 , -1);
            if ($fltr != '') {
                $fltr = 'AND(' . $fltr . ')';
                $DLparams['filters'] = $fltr;
                $_ = $this->modx->runSnippet("DocLister", $DLparams);
                $this->content_ids = $this->getListFromJson($_);
                //$this->content_ids = str_replace(' ', '', substr($this->content_ids, 0, -1));
            } else {
                if ($this->categoryAllProducts) {
                    $this->content_ids = $this->categoryAllProducts;
                }
            }
        }
    } else {//если ничего не искали и у нас есть список всех продуктов категории, их и ставим
        if ($this->categoryAllProducts) {
            $this->content_ids = $this->categoryAllProducts;
        }
    }

    $this->content_ids_cnt = $this->content_ids != '' ? count(explode(',', $this->content_ids)) : (!empty($this->fp) ? '0' : '-1');
    if ($this->content_ids_cnt != '-1' && $this->content_ids_cnt != '0') {
        $this->content_ids_cnt_ending = $this->getNumEnding($this->content_ids_cnt, $this->endings);
    } else if ($this->content_ids_cnt == '0') {
        $this->content_ids_cnt_ending = isset($this->endings[2]) ? $this->endings[2] : 'товаров';
    } else {
        $this->content_ids_cnt_ending = '';
    }
    return $this->content_ids;
}

public function makeCurrFilterValuesContentIDs ($DLparams)
{
    $content_ids_list = false;
    if (!empty($this->fp)) {//разбираем фильтры из строки GET и считаем возможные значения и количество для этих фильтров без учета одного из них (выбранного)
        $f = $this->fp;
        if (is_array($f)) {
            foreach ($this->filter_tv_names as $fid =>$name) {
                $fltr = '';
                if (isset($f[$fid])) {
                    foreach ($f as $tvid => $v) {
                        if ($tvid != $fid) {
                            $tvid = (int)$tvid;
                            $oper = 'eq';

                            switch(true) {

                                case $this->filters[$tvid]['type'] == '9':
                                    //одиночный чекбокс
                                    $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':isnotnull:;';
                                    break;

                                case (isset($v['min']) || isset($v['max'])):
                                    if (isset($v['min']) && (int)$v['min'] != 0 ) {
                                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':egt:' . (int)$v['min'] . ';';
                                    }
                                    if (isset($v['max']) && (int)$v['max'] != 0 ) {
                                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':elt:' . (int)$v['max'] . ';';
                                    }
                                    break;

                                default:
                                    if (is_array($v)) {
                                        if (!isset($this->params['allowZero'])) {
                                            foreach($v as $k1 => $v1) {
                                                if ($v1 == '0') {
                                                    unset($v[$k1]);
                                                }
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
                                            if (isset($this->params['useRegexp'])) {
                                                $oper = 'regexp';
                                                $val = '[[:<:]]' . str_replace(array(',', '||'), '[[:>:]]|[[:<:]]', $val) . '[[:>:]]';
                                            } else {
                                                $oper = 'containsOne';
                                            }
                                        }
                                        $val = str_replace(array('(', ')'), array('\(', '\)'), $val);
                                        $fltr .= $this->dl_filter_type . ':' . $this->filter_tv_names[$tvid] . ':' . $oper . ':' . $val.';';
                                    }
                                    break;
                            }
                        }
                    }
                    $fltr = substr($fltr, 0 , -1);
                }
                if ($fltr != '') {
                    $fltr = 'AND(' . $fltr . ')';
                    $DLparams['filters'] = $fltr;
                    $_ = $this->modx->runSnippet("DocLister", $DLparams);
                    $this->curr_filter_values[$fid]['content_ids'] = $this->getListFromJson($_);
                /*} else {
                    if (isset($f[$fid])) {
                        unset($DLparams['filters']);
                        if (!$content_ids_list) {
                            $_ = $this->modx->runSnippet("DocLister", $DLparams);
                            $content_ids_list = $this->getListFromJson($_);
                        }
                        $this->curr_filter_values[$fid]['content_ids'] = $content_ids_list;
                    } else {
                        $this->curr_filter_values[$fid]['content_ids'] = 'all';
                    }*/
                } else {
                    unset($DLparams['filters']);
                    if (isset($f[$fid])) {
                        if (!$content_ids_list) {
                            $_ = $this->modx->runSnippet("DocLister", $DLparams);
                            $content_ids_list = $this->getListFromJson($_);
                        }
                        $this->curr_filter_values[$fid]['content_ids'] = $content_ids_list;
                    } else {
                        if (isset($this->content_ids) && $this->content_ids != '') {
                            $this->curr_filter_values[$fid]['content_ids'] = 'all';
                        } else {
                            if (!$content_ids_list) {
                                $_ = $this->modx->runSnippet("DocLister", $DLparams);
                                $content_ids_list = $this->getListFromJson($_);
                            }
                            $this->curr_filter_values[$fid]['content_ids'] = $content_ids_list;
                        }
                    }
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

public function getFP () {
    //готовим почву для передачи нужных параметров фильтрации прямо при вызове фильтра
    //вида &fp=`f16=значение1,значение2&f17=значение3,значение4&f18=minmax~100,300`
    //todo seo url
    $this->fp = (isset($this->params['fp']) && !empty($this->params['fp'])) ? $this->params['fp'] : (isset($_GET) ? $_GET : array());
    return $this;
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
        $elements = $this->getTVNames($tvs, 'elements');
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

//возвращает список всех дочерних товаров категории плюс товаров, прикрепленных к категории тегом tagSaver через tv с id=$tv_id
public function getCategoryAllProducts($id, $tv_id)
{
    $seocat = false;
    if (!empty($this->modx->documentObject[$this->seocategorytv][1])) {
        //берем товары, которые принадлежат категории, указанной в tv с именем $this->seocategorytv
        $id = $this->modx->documentObject[$this->seocategorytv][1];
        $seocat = true;
    }
    //если хотим искать только по заданным документам, то до вызова [!eFilter!] устанавливаем их спискок в плейсхолдер eFilter_search_ids
    $search_ids = $this->modx->getPlaceholder("eFilter_search_ids");
    if ($search_ids && $search_ids != '') {
        $filter_ids = $this->modx->getPlaceholder("eFilter_filter_ids");
        if ($filter_ids && $filter_ids != '') {//если еще и установили ограничитель списка id в плейсхолдер eFilter_filter_ids
            $search_ids = implode(',', array_intersect(explode(',', $search_ids), explode(',', $filter_ids)));
        }
        $this->categoryAllProducts = $search_ids;
        return $search_ids;
    }
    //сначала ищем все товары, вложенные в данную категорию на глубину до 6
    $children = $this->getCategoryProductsChildren($id);

    //привязанные к категории товары через MultiCategories - http://modx.im/blog/addons/5700.html
    $children = $this->getCategoryProductsMultiCategories($id, $children);

    if (!empty($tv_id)) {
        //привязанные к категории товары через TagSaver
        $children = $this->getCategoryProductsTagSaver($id, $tv_id, $children);
    }
    if ($seocat && !empty($children)) {
        $children = $this->getSeoChildren($children);
    }
    $this->categoryAllProducts = implode(',', array_keys($children));
    return $this->categoryAllProducts;
}

public function getCategoryProductsChildren($id, $children = array(), $depth = 6)
{
    $p = array(
        'parents' => $id,
        'depth' => $depth,
        'JSONformat' => 'new',
        'api' => 'id',
        'selectFields' => 'c.id',
        'makeUrl' => '0',
        'debug' => '0',
        'addWhereList' => 'template IN (' . $this->product_templates_id . ')'
    );
    if(!empty($this->params['showParent'])) {
        $p['showParent'] = $this->params['showParent'];
    }
    if(!empty($this->params['addWhereList'])) {
        $p['addWhereList'] .= ' AND ' . $this->params['addWhereList'];
    }
    if(!empty($this->params['showNoPublish'])) {
        $p['showNoPublish'] = $this->params['showNoPublish'];
    }
    $filter_ids = $this->modx->getPlaceholder("eFilter_filter_ids");
    if (!empty($filter_ids)) {
        $p['addWhereList'] .= ' AND c.id IN (' . $filter_ids . ') ';
    }
    $json = $this->modx->runSnippet("DocLister", $p);
    if ($json && !empty($json)) {
        $arr = json_decode($json, true);
        if (!empty($arr) && isset($arr['rows'])) {
            $tmp2 = array();
            foreach ($arr['rows'] as $v) {
                $children[$v['id']] = 1;
            }
        }
    }
    return $children;
}

public function getCategoryProductsTagSaver($id, $tv_id, $children = array())
{
    //доп.условие, если задан сторонний ограничитель в плейсхолдере ранее
    $add_where = '';
    $filter_ids = $this->modx->getPlaceholder("eFilter_filter_ids");
    if (!empty($filter_ids)) {
        $add_where = ' AND b.doc_id IN (' . $filter_ids . ') ';
    }

    //берем id всех товаров, привязанных к этой категории через tv category id=$tv_id
    $tmp_parents = array($id);

    //а также - товары, прикрепленные ко всем дочерним "категориям" относительно текущей категории (через tv "категория")
    //нужны только папки, потому берем только из кэша
    $aliaslistingfolder = $this->modx->config['aliaslistingfolder'];
    $this->modx->config['aliaslistingfolder'] = '0';
    $childs = $this->modx->getChildIds($id, 5);
    $this->modx->config['aliaslistingfolder'] = $aliaslistingfolder;
    if (!empty($childs)) {
        //исключаем случайные "товары-папки" и кэшированные "непапки"
        $q1 = $this->modx->db->query("SELECT id FROM " . $this->modx->getFullTableName("site_content") . " WHERE id IN (" . implode(',', array_values($childs)) . ") AND deleted=0 AND published=1 AND isfolder=1 AND template NOT IN (" . $this->product_templates_id . ")");
        while($row = $this->modx->db->getRow($q1)) {
            $tmp_parents[] = $row['id'];
        }
    }
    //собираем все прикрепленные к данным папкам товары
    $sql = "SELECT a.*, b.* FROM " . $this->modx->getFullTableName("tags") . " a, " . $this->modx->getFullTableName("site_content_tags") . " b WHERE b.tv_id = " . $tv_id . " AND a.id = b.tag_id AND a.name IN (" . implode(",", $tmp_parents) . ")" . $add_where;
    $q = $this->modx->db->query($sql);
    while ($row = $this->modx->db->getRow($q)) {
        $children[$row['doc_id']] = 1;
    }
    return $children;
}

public function getCategoryProductsMultiCategories($id, $children = array())
{
    if (isset($this->params['useMultiCategories'])) {
        $categories = array();
        //добавляем дочерние категории
        $childs = $this->modx->getChildIds($id, 5);
        if (!empty($childs)) {
            $categories = array_values($childs);
        }
        //и саму категорию
        $categories[] = $id;
        //берем все товары данных мультикатегорий
        $q = $this->modx->db->query("SELECT * FROM " . $this->modx->getFullTableName("site_content_categories") . " WHERE category IN (" . implode(',', $categories) . ")");
        while ($row = $this->modx->db->getRow($q)) {
            $children[$row['doc']] = 1;
        }
    }
    return $children;
}

public function getNumEnding($number, $endingArray)
{
    $number = $number % 100;
    if ($number >= 11 && $number <= 19) {
        $ending=$endingArray[2];
    } else {
        $i = $number % 10;
        switch ($i) {
            case (1): $ending = $endingArray[0]; break;
            case (2):
            case (3):
            case (4): $ending = $endingArray[1]; break;
            default: $ending=$endingArray[2];break;
        }
    }
    return $ending;
}

public function setCommaAsSeparator()
{
    $this->commaAsSeparator = false;
    if (isset($this->params['commaAsSeparator'])) {
        $commaAsSeparator = trim($this->params['commaAsSeparator']);
        if ($commaAsSeparator == "all") {
            $this->commaAsSeparator = true;
        } else {
            $this->commaAsSeparator = array_map('trim', explode(',', $commaAsSeparator));
        }
    }
    return $this;
}

public function getSeoChildren($children)
{
    $out = array();
    $common_tv_names = $this->getTVNames (implode(',', array_keys($this->common_filters)));
    $tvs = $this->modx->getTemplateVarOutput(array_keys($this->common_filters), $this->modx->documentIdentifier);
    $seoFilters = array();
    foreach ($this->common_filters as $k => $v) {
        $seo_tv_name = !empty($common_tv_names[$k]) ? $common_tv_names[$k] : '';
        if (empty($seo_tv_name) || empty($tvs[$seo_tv_name])) continue;
        switch ($v['type']) {
            case '3':case '6':
                //диапазон-слайдер
                $minmax = array_map('trim', explode('-', $tvs[$seo_tv_name]));
                if (!empty($minmax[0])) {
                    $seoFilters[] = $this->dl_filter_type . ':' . $seo_tv_name . ':>=:' . $minmax[0];
                }
                if (!empty($minmax[1])) {
                    $seoFilters[] = $this->dl_filter_type . ':' . $seo_tv_name . ':<=:' . $minmax[1];
                }
                break;
            default:
                if (empty($v['many'])) {
                    $seoFilters[] = $this->dl_filter_type . ':' . $seo_tv_name . ':=:' . $tvs[$seo_tv_name];
                } else {
                    if (isset($this->params['useRegexp'])) {
                        $seoFilters[] = $this->dl_filter_type . ':' . $seo_tv_name . ':regexp:' . '[[:<:]]' . str_replace(array(',', '||'), '[[:>:]]|[[:<:]]', $tvs[$seo_tv_name]) . '[[:>:]]';
                    } else {
                        $seoFilters[] = $this->dl_filter_type . ':' . $seo_tv_name . ':containsOne:' . str_replace('||', ',', $tvs[$seo_tv_name]);
                    }
                }
                break;
        }
    }
    if (!empty($seoFilters)) {
        $DLparams = array('api' => 'id', 'JSONformat' => 'new', 'documents' => implode(',', array_keys($children)), 'sortType' => 'doclist', 'filters' => 'AND(' . implode(';', $seoFilters) . ')');
        $seo_dl = $this->modx->runSnippet("DocLister", $DLparams);
        $ids = $this->getListFromJson($seo_dl);
        if (!empty($ids)) {
            $out = array_flip(explode(',', $ids));
        }
    }
    return $out;
}

public function translate($str, $default = null)
{
    $str = trim($str);
    if(empty($str)) return;
    switch(true) {
        case !empty($this->params['evobabel']):
            $translation = $this->modx->runSnippet("lang", [ "a" => $str ]);
            break;
        case !empty($this->params['blang']):
            $translation = $this->modx->getConfig('__' . $str);
            break;
        case !empty($this->params['translator']) && is_callable($this->params['translator']):
            $translation = call_user_func($this->params['translator'], $str, $default);
            break;
        default:
            $translation = $str;
            break;
    }
    return is_null($translation) ? ($default ?: $str) : $translation;
}

}
