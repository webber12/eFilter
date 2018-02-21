//<?php
/**
 * tovarParams
 * 
 * Параметры товара в шаблон товара
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    snippet
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 */
 
// импортировать общие параметры из модуля eLists
// предназначен для вывода параметров товара в нужном месте шаблона товара
// пример вызова [[tovarParams]] - в нужном месте шаблона "Товар"
// доп.параметры - большинство импортируется из модуля, для вывода списка параметров $paramRow и $paramOuter


//массив id тв, разрешенных для данного типа товаров в конфиге родителя
$allowedParams = array();

//заменяем плейсхолдер [+params+] в чанке вывода товаров 
//на нужный вывод параметров товаров
//шаблоны вывода параметра в списке по умолчанию
$paramRow = isset($paramRow) ? $paramRow : '<div class="eFilter_list_param eFilter_list_param[+param_id+]"><span class="eFilter_list_title">[+param_title+]: </span><span class="eFilter_list_value eFilter_list_value[+param_id+]">[+param_value+]</span></div>';
$paramOuter = isset($paramOuter) ? $paramOuter : '<div class="eFilter_item_params">[+wrapper+]</div>';

$out = '';
$tovar_params_tpl = '';

include_once(MODX_BASE_PATH . 'assets/snippets/eFilter/eFilter.class.php');
$eFltr = new eFilter($modx, $params);
$eFltr->docid = $modx->documentObject['parent'];


//получаем общий список тв-параметров из категорий "параметры для товара" - $param_cat_id
$tv_list = array();
$sql = "SELECT a.`id`,a.`name`,a.`caption`,a.`elements` FROM " . $modx->getFullTableName('site_tmplvars') . " as a, " . $modx->getFullTableName('site_tmplvar_templates') . " as b WHERE a.`category` IN (" . $param_cat_id . ") AND `a`.`id` = `b`.`tmplvarid` AND `b`.`templateid` IN(" . $params['product_templates_id'] . ")  ORDER BY b.`rank` ASC, a.`caption` ASC";

$q = $modx->db->query($sql);
while($row = $modx->db->getRow($q)){
    if (!isset($tv_list[$row['id']])) {
        $tv_list[$row['id']]['name'] = $row['name'];
        $tv_list[$row['id']]['caption'] = $row['caption'];
        $tv_list[$row['id']]['elements'] = $row['elements'];
    }
}

//находим разрешенные для данного товара параметры
//сначала ищем в родителе
$allowedParams = array();
$doc = $modx->documentIdentifier;
$allowedTmp = $eFltr->getFilterParam ($eFltr->param_tv_name);

//если тут пусто, проверим первую тегованную категорию из параметра tv_category_tag
if (isset($tv_category_tag) && $tv_category_tag != '' && empty($allowedTmp)) {
    $q = $modx->db->getValue("SELECT value FROM " . $modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid={$tv_category_tag} AND contentid=" . $modx->documentIdentifier);
    if ($q) {
        $cats = explode(',', $q);
        if (isset($cats[0]) && (int)$cats[0] > 0) {
            $allowedTmp = $eFltr->getFilterParam ( $eFltr->param_tv_name, (int)$cats[0]);
        }
    }
}

//итоговый массив разрешенных для данного товара параметров
if (isset($allowedTmp['fieldValue'])) {
    foreach ($allowedTmp['fieldValue'] as $k=>$v) {
        $allowedParams[$v['param_id']] = '1';
    }
}


//оставляем только разрешенные для данного товара параметры в списке
foreach ($tv_list as $k => $v) {
    if (!isset($allowedParams[$k])) {
        unset($tv_list[$k]);
    }
}

// удаляем из списка общие исключенные ТВ (в настройках модуля) -
// (например цена и т.п., которая выводится отдельно и есть у всех
if (isset($exclude_tvs_from_list) && $exclude_tvs_from_list != '') {
    $exclude_tvs = explode(',', $exclude_tvs_from_list);
    foreach($exclude_tvs as $k=>$v){
        if (isset($tv_list[$v])) {
            unset($tv_list[$v]);
        }
    }
}
///////


foreach($tv_list as $tv_id=>$v) {
    $param_title = $v['caption'];
    $param_value = '[*' . $v['name'] . '*]';
    $param_value = stristr($v['elements'], 'getParamsFromTree') === FALSE ? '[*' . $v['name'] . '*]' : '[[if? &is=`[*' . $v['name'] . '*]:!empty` &then=`[[multiParams? &action=`showParamsFromTree` &ids=`[*' . $v['name'] . '*]`]]`]]';

    $tovar_params_tpl .= $eFltr->parseTpl(
        array('[+param_title+]', '[+param_value+]', '[+param_id+]'),
        array($param_title, $param_value, $tv_id),
        $paramRow
    );
}

$out = $eFltr->parseTpl(
    array('[+wrapper+]'),
    array($tovar_params_tpl),
    $paramOuter
);

return $out;
