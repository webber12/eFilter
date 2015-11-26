//<?php
/**
 * multiParams
 * 
 * Параметры товара для категории
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    snippet
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 */
 
 //импортировать общие параметры из модуля eLists
 
 //служебный сниппет для формирования возможных значений из списков из модуля, дерева либо нужных категорий TV

 // использование
 // если вы храните списки возможных значений в модуле eLists, то просто скопируйте соответствующую строку в поле "возможные значения" нужного TV 
 // (тип выставляете который требуется - селект, чекбоксы, радио и т.п.)
 // @EVAL return $modx->runSnippet("multiParams", array("parent"=>"2")); - выглядит это примерно так в модуле
 // где 2 - это id родителя нужного списка в отдельной таблице (он формируется автоматом)
 // 
 // если вам не нужно первое пустое значение в tv (например, у вас тип ввода - чекбокс- вызывайте multiParams с дополнительным параметром "firstEmpty" => "0"
 //
 // если же вы предпочитаете хранить списки возможных значений TV в дереве, то добавляйте к вызову в поле "возможные значения" дополнительный параметр 'action'=>'getParamsFromTree'
 // т.е. итоговый вызов в поле "возможные значения" для TV будет выглядеть так
 // @EVAL return $modx->runSnippet("multiParams", array("parent"=>"25", "action"=>"getParamsFromTree"));
 // где 25 - это id ресурса-родителя нужного списка в дереве, его подставляем самостоятельно
 // 
 // @EVAL return $modx->runSnippet("multiParams", array("field"=>"template", "value"=>"15", "action"=>"getParamsFromTree"));
 // позволяет в выпадающий список вывести все ресурсы с template=15 (для фильтрации можно использовать любое поле из таблицы site_content
 //
 // @EVAL return $modx->runSnippet("multiParams", array("field"=>"description","value"=>"58", "action"=>"getParamsFromTree", "order" => "menuindex ASC", "firstEmpty" => "0"));
 // выбираем в выпадающий список все ресурсы у которых в поле description значение 58, сортируем по menuindex с возрастанием, первый пустой не показываем (важно для вывода в виде чекбоксов)
 // сортировка по умолчанию - сначала по pagetitle по возрастанию, потом по menuindex по возрастанию
 //
 
$out = '';
$firstEmpty = isset($firstEmpty) && (int)$firstEmpty == 0 ? false : true;
$order = isset($order) && !empty($order) ? $order : "pagetitle ASC, menuindex ASC";
if ($firstEmpty) {
    $out .= '||';
}
switch ($action){
    case 'getParamsToMultiTV' :
        $sql = "SELECT `id`,`caption` FROM " . $modx->getFullTableName('site_tmplvars') . " WHERE `category` IN (" . $param_cat_id . ") ORDER BY `rank` ASC, `caption` ASC";
        $q = $modx->db->query($sql);
        while($row = $modx->db->getRow($q)){
            $out .= $row['caption'] . '==' . $row['id'] . '||';
        }
        break;
    
    case 'getParamsFromTree' :
        if (isset($field) && isset($value)) {
            $sql = "SELECT pagetitle, id FROM " . $modx->getFullTableName('site_content') . " WHERE `" . $field . "`='" . $value . "' ORDER BY " . $order;
        } else {
            $sql = "SELECT pagetitle, id FROM " . $modx->getFullTableName('site_content') . " WHERE parent IN(" . $parent . ") ORDER BY " . $order;
        }
        $q = $modx->db->query($sql);
        while ($row = $modx->db->getRow($q)) {
            //в выпадающем списке админки показываем вместе с id ресурса
            $out .= $row['pagetitle'] . (strpos($_SERVER['REQUEST_URI'], MGR_DIR) !== FALSE ? ' (' . $row['id'] . ')' : '') . '==' . $row['id'] . '||';
        }
        break;
    
    default:
        $sql = "SELECT title, id FROM " . $modx->getFullTableName('list_value_table') . " WHERE parent={$parent} ORDER BY sort ASC, title ASC";
        $q = $modx->db->query($sql);
        while ($row = $modx->db->getRow($q)) {
            $out .= $row['title'] . '||';
        }
        break;
}
$out = substr($out, 0, -2);
return $out;
