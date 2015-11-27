//<?php
/**
 * makeFilterResult
 * 
 * Делаем простой фильтр по всем заданным для категории TV
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@modx_category Filters
 * @internal    @installset base, sample
 */
 
 
 // вызываем [!makeFilterResult ...!] со всеми нужными параметрами аналогично DocLister где-нибудь в шапке, чтобы по всей странице стали доступны установленные плейсхолдеры
 // для сортировки по умолчанию используем параметры sortBy и sortOrder по раздельности (вместо orderBy)
 // в месте вывода каталога используем плейсхолдеры [+catalog+] и [+pages+]
 // для получения списка документов в json в других сниппетах используем плейсхолдер [+catalogJSON+] ($modx->getPlaceholder('catalogJSON');)
 // этот массив нам понадобится в сниппетах makeFilter / makeFilters для отбрасывания лишних "возможных значений"
 //

$out = '';
//сразу сохраняем начальные параметры вызова
$params_snippet = $params;

$docid = isset($docid) ? (int)$docid : $modx->documentIdentifier;
$display = isset($_SESSION['sortDisplay']) ? $modx->db->escape($_SESSION['sortDisplay']) : ($params['display'] ? $params['display'] : '12');
$sortBy = isset($_SESSION['sortBy']) ? $modx->db->escape($_SESSION['sortBy']) : ($params['sortBy'] ? $params['sortBy'] : 'menuindex');
$sortOrder = isset($_SESSION['sortOrder']) ? $modx->db->escape($_SESSION['sortOrder']) : ($params['sortOrder'] ? $params['sortOrder'] : 'DESC');
$params['orderBy'] = $sortBy . ' ' . $sortOrder;

if(!function_exists(clean_in)) {
	function clean_in($in) {
		$tmp = explode(',', $in);
		return "'" . implode("','", $tmp) . "'";
	}
}

$template = $modx->documentObject['template'];
$parent = $modx->documentObject['parent'];

//флаг, что искали по тегам. Если пусто - значит искали =true, но ничего не нашли (выводим, что пусто, а не все товары)
$docs_flag = false;

$q = $modx->db->query("SELECT id,name FROM ". $modx->getFullTableName("site_tmplvars"));
$tv_list = array();
while ($row = $modx->db->getRow($q)) {
	if (!isset($tv_list[$row['id']])) {
		$tv_list[$row['id']] = $row['name'];
	}
}

//теперь формируем дополнительные фильтро-запросы
$filters = array();
$tmp_filter = array();
if (isset($_GET)) {
	foreach ($_GET as $k => $v) {
		$op = 'eq';
		if (stripos($k, 'min') !== false) {
			$k = str_replace('min_', '', $modx->db->escape($k));
			$op = '>=';
			$val = (double)$v;
		} else if (stripos($k, 'max') !== false) {
			$k = str_replace('max_', '', $modx->db->escape($k));
			$op = '<=';
			$val = (double)$v;
		} else {
			$k = $modx->db->escape($k);
			if (is_scalar($v)) {
				$val = $modx->db->escape($v);
			} else {
				$val = implode(',', $v);
				if (count($v) > 1) {
					$op = 'in';
				}
			}
		}
		if ($val != '' && $val != '0') {
			if (in_array($k, $tv_list)) {//это ТВ
					$tmp_filter['list'][] = 'tv:' . $k . ':' . $op . ':' . $val . ';';
				} else {//не ТВ, его никуда не трогаем
				
				}
		}
	}
}

if (!empty($tmp_filter) && isset($tmp_filter['list'])) {
	$filters = $tmp_filter['list'];
}

$_docs = array();
//массив документов для json-запроса всех находящихся в данном разделе/категории/виде товара документов для формирования фильтра
$json_docs = array();


$params_tmp = array(
	'tvSortType' => 'UNSIGNED',
	'display' => $display,
);

if ($display == 'all') unset($params_tmp['display']);

if (!empty($filters)) {
	$params_tmp['filters'] = 'AND(' . implode(';', $filters) . ')';
}

if (!empty($_docs)) {
	$docs_flag = true;
	$__docs = $_docs[0];
	foreach ($_docs as $___docs) {
		if (!empty($___docs) && !empty($__docs)) {
			$__docs = array_intersect_key($__docs, $___docs);
		} else {
			$__docs = array();
		}
	}
}

if (!empty($json_docs)) {
	$_json_docs = $json_docs[0];
	foreach ($json_docs as $__json_docs) {
		if (!empty($__json_docs) && !empty($_json_docs)) {
			$_json_docs = array_intersect_key($_json_docs, $__json_docs);
		} else {
			$_json_docs = array();
		}
	}
}

$documents = !empty($__docs) ? implode(',', array_keys($__docs)) : '';

if (!empty($documents)) {
	$params_tmp['addWhereList'] = !empty($params['addWhereList']) ? $params['addWhereList'] . ' AND c.id IN(' . $documents . ')' : 'c.id IN(' . $documents . ')';
} else {
	if ($docs_flag) {
		$params_tmp['addWhereList'] = !empty($params['addWhereList']) ? $params['addWhereList'] . ' AND c.id IN(1500000000)' : 'c.id IN(' . $documents . ')';
	}
}

$params = array_merge($params, $params_tmp);
$out .= $modx->runSnippet("DocLister", $params);
$modx->setPlaceholder('catalog', $out);


//а теперь вернем просто список id в json для расчета цены, выборки производителей и т.п.
//print_r($params);echo '<hr>';
unset($params['tvList']);
unset($params['prepare']);
unset($params['paginate']);
unset($params['display']);
unset($params['filters']);
unset($params['renderTV']);
$params['addWhereList'] = (isset($params_snippet['addWhereList']) ? $params_snippet['addWhereList'] : '') . (!empty($_json_docs) ? ' AND c.id IN (' . implode(',', array_keys($_json_docs)) . ')' : '');
$json_params = array('JSONformat' => 'new', 'api' => '1', 'selectFields' => 'c.id', 'makeUrl' => '0');
$params = array_merge($params, $json_params);
$json = $modx->runSnippet("DocLister", $params);
$modx->setPlaceholder('catalogJSON', $json);

