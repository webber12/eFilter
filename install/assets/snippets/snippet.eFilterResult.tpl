//<?php
/**
 * eFilterResult
 * 
 * Вывод отфильтрованных товаров
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    snippet
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 */
 
 //импортировать общие параметры из модуля eLists
 //использует для работы сниппет DocLister и выводит список товаров, при этом заменяя плейсхолдер [+params+] на список параметров товара, отмеченных для вывода в список
 //использует общие параметры из модуля eLists, также параметры $paramRow и $paramOuter для вывода параметров товара
 //доп.информация черпается из плейсхолдеров, установленных сниппетом [!eFilter!] который должен вызываться раньше
 //пример вызова [!eFilterResult? &tpl=`tovarDL` &addWhereList=`c.template=9` &parents=`[*id*]` &depth=`3` &paginate=`pages` &display=`15` &tvList=`image,price`!] [+pages+]
 //все параметры аналогичны параметрам вызова DocLister + доп.параметры $paramRow и $paramOuter для вывода параметров товара

 
//получаем из плейсхолдера список документов для documents
$ids = $modx->getPlaceholder('eFilter_ids');

//фиксим DocLister - при пустом списке documents и пустом фильтре - отдавать все
//при пустом списке documents и НЕ пустом фильтре - ничего не отдавать
if ($ids == '' && !empty($_GET)) {
	$isFilterActive = false;
	
	if (isset($_GET['f']) && is_array($_GET['f'])) {
		foreach ($_GET['f'] as $k => $v) {
			if ($isFilterActive) {
				break;
			}
			
			foreach ($v as $val) {
				if (!empty($val)) {
					$isFilterActive = true;
					break;
				}
			}
		}
	} else {
		foreach ($_GET as $k => $v) {
			if (!empty($v) && is_scalar($v) && preg_match('/^f\d+/i', $k)) {
				$isFilterActive = true;
				break;
			}
		}
	}
	
	if ($isFilterActive) {
		$ids = PHP_INT_MAX;
	}
}

//получаем из плейсхолдера список ТВ для вывода в список
$tv_list = $modx->getPlaceholder('eFilter_tv_list');
//..и их имена из кэпшн
$tv_names = $modx->getPlaceholder('eFilter_tv_names');

// удаляеи из списка общие исключенные ТВ (в настройках модуля) -
// (например цена и т.п., которая выводится отдельно и есть у всех
if (isset($exclude_tvs_from_list) && $exclude_tvs_from_list != '') {
	$exclude_tvs = explode(',', $exclude_tvs_from_list);
	foreach($exclude_tvs as $k=>$v){
		if (isset($tv_names[$v])) {
			unset($tv_names[$v]);
		}
		if (isset($tv_list[$v])) {
			unset($tv_list[$v]);
		}
	}
}
///////



//заменяем плейсхолдер [+params+] в чанке вывода товаров 
//на нужный вывод параметров товаров
//шаблоны вывода параметра в списке по умолчанию
$paramRow = isset($paramRow) ? $paramRow : '<div class="eFilter_list_param eFilter_list_param[+param_id+]"><span class="eFilter_list_title">[+param_title+]: </span><span class="eFilter_list_value eFilter_list_value[+param_id+]">[+param_value+]</span></div>';
$paramOuter = isset($paramOuter) ? $paramOuter : '<div class="eFilter_list_params">[+wrapper+]</div>';


$tovar_params_tpl = '';
foreach($tv_names as $tv_id=>$tv_name) {
	$param_value = '[+tv.' . $tv_list[$tv_id] . '+]';
	$tovar_params_tpl .= str_replace(
		array('[+param_title+]', '[+param_value+]', '[+param_id+]'),
		array($tv_name, $param_value, $tv_id),
		$paramRow
	);
}

$tovar_params_wrapper = str_replace(
	array('[+wrapper+]'),
	array($tovar_params_tpl),
	$paramOuter
);

$tovarChunkName = isset($params['tpl']) && !empty($params['tpl']) ? $params['tpl'] : $tovarChunkName;
$tovarChunk = $modx->getChunk($tovarChunkName);
$tovarChunk = '@CODE: ' . str_replace('[+params+]', $tovar_params_wrapper, $tovarChunk);
$params['tpl'] = $tovarChunk;
///////конец замены чанка вывода товаров


$out = '';
$pid = isset($pid) ? $pid : $modx->documentIdentifier;
$params['ownerTPL'] = isset($ownerTPL) ? $ownerTPL :'@CODE: <div id="eFiltr_results_wrapper"><div class="eFiltr_loader"></div><div id="eFiltr_results">[+dl.wrap+][+pages+]</div></div>';

//параметры сортировки и вывода из сессии
$docid = isset($docid) ? (int)$docid : $modx->documentIdentifier;
$display = isset($_SESSION['sortDisplay']) ? $modx->db->escape($_SESSION['sortDisplay']) : ($params['display'] ? $params['display'] : '12');
$sortBy = isset($_SESSION['sortBy']) ? $modx->db->escape($_SESSION['sortBy']) : ($params['sortBy'] ? $params['sortBy'] : 'menuindex');
$sortOrder = isset($_SESSION['sortOrder']) ? $modx->db->escape($_SESSION['sortOrder']) : ($params['sortOrder'] ? $params['sortOrder'] : 'DESC');
$params['orderBy'] = $sortBy . ' ' . $sortOrder;
$params['display'] = $display;
if ($display == 'all') unset($params['display']);

if ($ids) {
    $params['documents'] = $ids;
    unset($params['parents']);
    unset($params['depth']);
} else {
    $params['parents'] = $pid;
}
$params['addWhereList'] = 'c.template IN(' . $product_templates_id . ')';
if (!empty($tv_list)) {
    $params['tvList'] = $params['tvList'] == '' ? implode(',', $tv_list) : $params['tvList'] . ',' . implode(',', $tv_list);
    $params['renderTV'] = $params['renderTV'] == '' ? implode(',', $tv_list) : $params['renderTV'] . ',' . implode(',', $tv_list);
}
$params['tvSortType'] = 'UNSIGNED';
if (!empty($params)) {
    $out .= $modx->runSnippet("DocLister", $params);
}
//Найдено [+count+], показано с [+eFRes_from+] по [+eFRes_to+]
$DL_id = isset($params['id']) && !empty($params['id']) ? $params['id'] . '.' : '';
if ($count == '0') { 
    $from = $to = 0;
} else {
    $display = $modx->getPlaceholder($DL_id . 'display');
    $current = $modx->getPlaceholder($DL_id . 'current');
    $from = ($current - 1) * $params['display'] + 1;
    $to = $from - 1 + $display;
}
$modx->setPlaceholder("eFRes_from", $from);
$modx->setPlaceholder("eFRes_to", $to);

return $out;
