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
 // дополнительные параметры (опционально)
 // &sortBy - по умолчанию menuindex (может быть как поле из site_content, так и любое ТВ, которое выводится в списке через DocLister и, соответветственно указано в его параметре tvList
 // &sortOrder - ASC | DESC (по умолчанию DESC)
 // &config_sort - конфиг параметров сортировки (первая часть до || - заголовок, остальные - варианты. Может быть как поле site_content , так и приемлемый для DocLister TV). По умолчанию - Сортировать по:||pagetitle==Названию||price==Цене (по названию и цене)
 // &config_display - настройка селекта "показывать по". По умолчанию - Показывать по:||==--не выбрано--||10||20||30||40||all
 // &sortRow
 // &sortOuter
 // &displayRow
 // &displayOuter
 // &classActiveName
 // &classUpName
 // &classDownName
 // &classSelectedName
 
$param = $modx->event->params;
 
$sortBy = isset($_SESSION['sortBy']) ? $_SESSION['sortBy'] : (isset($param['sortBy']) ? $param['sortBy'] : 'menuindex');
$sortOrder = isset($_SESSION['sortOrder']) ? $_SESSION['sortOrder'] : (isset($param['sortOrder']) ? $param['sortOrder'] : 'DESC');
$sortDisplay = isset($_SESSION['sortDisplay']) ? $_SESSION['sortDisplay'] : isset($param['display']) ? $param['display'] : '12';
$config_sort = isset($param['config_sort']) ? $param['config_sort'] : 'Сортировать по:||pagetitle==Названию||price==Цене';
$config_display = isset($param['config_display']) ? $param['config_display'] : 'Показывать по:||==--не выбрано--||10||20||30||40||all==все';
$sortRow = isset($param['sortRow']) ? $param['sortRow'] : '<a href="#" class="sorter sort_vid sort_pic [+classActive+] [+classUpDown+]" data-sort-by="[+sortBy+]" data-sort-order="[+sortOrder+]">[+title+]</a>';
$sortOuter = isset($param['sortOuter']) ? $param['sortOuter'] : '<div class="eFilter_sort_block"><span class="eFilter_sort_title">[+title+]</span><span class="eFilter_sort_options">[+rows+]</span></div>';
$displayRow = isset($param['displayRow']) ? $param['displayRow'] : '<option value="[+value+]" [+selected+]>[+title+]</option>';
$displayOuter = isset($param['displayOuter']) ? $param['displayOuter'] : '
    <div class="eFilter_display_block">
        <span class="eFilter_display_title">[+title+]</span>
        <span class="eFilter_display_options"><select name="sortDisplay" class="eFilter_display_select">[+rows+]</select></span>
    </div>';
$classActiveName = isset($param['classActiveName']) ? $param['classActiveName'] : 'active';
$classUpName = isset($param['classUpName']) ? $param['classUpName'] : 'up';
$classDownName = isset($param['classDownName']) ? $param['classDownName'] : 'down';
$classSelectedName = isset($param['classSelectedName']) ? $param['classSelectedName'] : 'selected';
$action_uri = $_SERVER['REQUEST_URI'];

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
$sortRows = '';
foreach ($cfg['sort']['values'] as $k => $v) {
    $classActive = $sortBy == $k ? ' ' . $classActiveName . ' ' : '';
    $classUpDown = !empty($classActive) ? (($sortOrder == 'ASC' ? ' ' . $classUpName. ' ' : ' ' . $classDownName. ' ')) : '';
    $sortOrderDirection = $sortOrder == 'ASC' ? 'DESC' : 'ASC';
    $sortRows .= str_replace(
        array('[+classActive+]', '[+classUpDown+]', '[+sortBy+]', '[+sortOrder+]', '[+title+]'),
        array($classActive, $classUpDown, $k, $sortOrderDirection, $v),
        $sortRow
    );
}
$sortBlock .= str_replace(
    array('[+title+]', '[+rows+]'),
    array($cfg['sort']['title'], $sortRows),
    $sortOuter
);

//блок "показать по"
$displayBlock = '';
$displayRows = '';
foreach ($cfg['display']['values'] as $k => $v) {
    $selected = $sortDisplay == $k ? ' ' . $classSelectedName : '';
    $displayRows .= str_replace(
        array('[+value+]', '[+selected+]', '[+title+]'),
        array($k, $selected, $v),
        $displayRow
    );
}
$displayBlock .= str_replace(
    array('[+title+]', '[+rows+]'),
    array($cfg['display']['title'], $displayRows),
    $displayOuter
);


//итоговая форма
$out .= <<<HTML
<div id="eFilter_sort_block">
<form action="{$action_uri}" method="post" id="changesortBy">
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
