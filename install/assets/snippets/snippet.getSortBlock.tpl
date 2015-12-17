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
 
 // вызываем в нужном месте getSortBlock
 // требует подключенного к странице jquery версии не ниже 1.9
 // дополнительные параметры
 // &sortBy - по умолчанию menuindex (может быть как поле из site_content, так и любое ТВ, которое выводится в списке через DocLister и, соответветственно указано в его параметре tvList
 // &sortOrder - ASC | DESC (по умолчанию DESC)
 // &config_sort - конфиг параметров сортировки (первая часть до || - заголовок, остальные - варианты. Может быть как поле site_content , так и приемлемый для DocLister TV). По умолчанию - Сортировать по:||pagetitle==Названию||price==Цене (по названию и цене)
 // &config_display - настройка селекта "показывать по". По умолчанию - Показывать по:||==--не выбрано--||10||20||30||40||all
 
 
$sortBy = isset($_SESSION['sortBy']) ? $_SESSION['sortBy'] : (isset($param['sortBy']) ? $param['sortBy'] : 'menuindex');
$sortOrder = isset($_SESSION['sortOrder']) ? $_SESSION['sortOrder'] : (isset($param['sortOrder']) ? $param['sortOrder'] : 'DESC');
$sortDisplay = isset($_SESSION['sortDisplay']) ? $_SESSION['sortDisplay'] : isset($param['display']) ? $param['display'] : '12';
$config_sort = isset($param[$config_sort]) ? $param[$config_sort] : 'Сортировать по:||pagetitle==Названию||price==Цене';
$config_display = isset($param[$config_display]) ? $param[$config_display] : 'Показывать по:||==--не выбрано--||10||20||30||40||all==все';

//разбираем конфиг
$cfg = array();
$tmp = explode("||", $config_sort);
foreach ($tmp as $k => $v) {
    if ($k == '0') {
        $cfg['sort']['title'] = $v;
    } else {
        $_tmp = explode("==", $v);
        $cfg['sort']['values'][$_tmp[0]] = (isset($_tmp[1]) && !empty($_tmp[1])) ? $_tmp[1] : $_tmp[0];
    }
}
$tmp = explode("||", $config_display);
foreach ($tmp as $k => $v) {
    if ($k == '0') {
        $cfg['display']['title'] = $v;
    } else {
        $_tmp = explode("==", $v);
        $cfg['display']['values'][$_tmp[0]] = (isset($_tmp[1]) && !empty($_tmp[1])) ? $_tmp[1] : $_tmp[0];
    }
}


$out = '';

//блок сортировки
$sortBlock = '';
foreach ($cfg['sort']['values'] as $k => $v) {
    $sortBlock .= '<a href="#" class="sorter sort_vid sort_pic ' . ($sortBy == $k ? 'active ' . ($sortOrder == 'ASC' ? ' up' : ' down') : '' ) . '" data-sort-by="' . $k . '" data-sort-order="' . ($sortOrder == 'ASC' ? 'DESC' : 'ASC') . '">' . $v . '</a>';
}
$sortBlock = '<div class="eFilter_sort_block"><span class="eFilter_sort_title">' . $cfg['sort']['title'] . '</span><span class="eFilter_sort_options">' . $sortBlock . '</span></div>';

//блок "показать по"
$displayBlock = '';
foreach ($cfg['display']['values'] as $k => $v) {
    $displayBlock .= '<option value="' . $k . '"' . ($sortDisplay == $k ? ' selected' : '') . '>' . $v . '</option>';
}
$displayBlock = '
    <div class="eFilter_display_block">
        <span class="eFilter_display_title">' . $cfg['display']['title'] . '</span>
        <span class="eFilter_display_options"><select name="sortDisplay" class="eFilter_display_select">' . $displayBlock . '</select></span>
    </div>';


//итоговая форма
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
        $("#eFilter_sort_block a.sort_vid").removeClass("active");
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
