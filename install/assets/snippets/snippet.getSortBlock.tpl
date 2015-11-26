//<?php
/**
 * getSortBlock
 * 
 * Формируем блок сортировки в каталог
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    snippet
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 */
 
$sortBy = isset($_SESSION['sortBy']) ? $_SESSION['sortBy'] : '';
$sortOrder = isset($_SESSION['sortOrder']) ? $_SESSION['sortOrder'] : 'DESC';
$sortDisplay = isset($_SESSION['sortDisplay']) ? $_SESSION['sortDisplay'] : '12';

$out = '';
$sortBlock = '<div class="eFilter_sort_block">
			Сортировать по: <a href="#" class="sorter sort_vid sort_pic ' . ($sortBy == 'price' ? 'active ' . ($sortOrder == 'ASC' ? ' up' : ' down') : '' ) . '" data-sort-vid="price" data-sort-order="' . ($sortOrder == 'ASC' ? 'DESC' : 'ASC') . '">цене</a>
			<a href="#" class="sorter sort_vid sort_pic ' . ($sortBy == 'pagetitle' ? 'active ' . ($sortOrder == 'ASC' ? ' up' : ' down') : '' ) . '" data-sort-vid="pagetitle" data-sort-order="' . ($sortOrder == 'ASC' ? 'DESC' : 'ASC') . '">названию</a>
		</div>';
$displayBlock = '<select name="sortDisplay">';
$displayBlock .= '
					<option value="">--Показывать по:--</option>
					<option value="10"' . ($sortDisplay == '10' ? ' selected' : '') . '>10</option>
					<option value="20"' . ($sortDisplay == '20' ? ' selected' : '') . '>20</option>
					<option value="30"' . ($sortDisplay == '30' ? ' selected' : '') . '>30</option>
					<option value="40"' . ($sortDisplay == '40' ? ' selected' : '') . '>40</option>
					<option value="all"' . ($sortDisplay == 'all' ? ' selected' : '') . '>Все</option>
		';
$displayBlock .= '</select>';

$out .= <<<HTML
<div id="eFilter_sort_block">
<form action="[~[*id*]~]" method="post" id="changesortBy">
	{$sortBlock}
	{$displayBlock}
	<input type="hidden" name="action" value="changesortBy">
	<input type="hidden" name="sortBy" value="{$sortBy}">
	<input type="hidden" name="sortOrder" value="{$sortOrder}">
</form>
<script>
$(document).ready(function(){
	$("#eFilter_sort_block").on("click", "a.sort_vid", function(e){
		e.preventDefault();
		var sortBy = $(this).data("sortBy");
		var sortOrder = $(this).data("sortOrder");
		$(".catalog_sort a.left").removeClass("active");
		$(this).addClass("active");
		$("input[name='sortBy']").val(sortBy);
		$("input[name='sortOrder']").val(sortOrder);
		$("#changesortBy").submit();
	})
	$("#eFilter_sort_block").on("change", "select[name='sortDisplay']", function(e){
		e.preventDefault();
		$("#changesortBy").submit();
	})
})
</script>
HTML;
return $out;
