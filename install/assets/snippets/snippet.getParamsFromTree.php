//<?php
/**
 * getParamsFromTree
 * 
 * Достаем из дерева исходные значения параметра по его id
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    snippet
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 */
 
 
//вызывается автоматически в сниппете tovarParams на странице товаров в случае, если
//значения tv формировались из дерева сниппетом multiParams

$ids = isset($params['ids']) ? $params['ids'] : '';
$arr = array();
if ($ids != '') {
	$ids = str_replace('||', ',', $ids);
	$q = $modx->db->query("SELECT id,pagetitle FROM modx_site_content WHERE id IN (" . $ids . ") AND published=1 AND deleted=0");
	while ($row = $modx->db->getRow($q)) {
		$arr[] = '<a href="' . $modx->makeUrl($row['id']) . '">' . $row['pagetitle'] . '</a>';
	}
}
$out = implode(', ', $arr);
return $out;
