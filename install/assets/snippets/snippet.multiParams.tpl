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
 // если же вы предпочитаете хранить списки возможных значений TV в дереве, то добавляйте к вызову в поле "возможные значения" дополнительный параметр 'action'=>'getParamsFromTree'
 // т.е. итоговый вызов в поле "возможные значения" для TV будет выглядеть так
 // @EVAL return $modx->runSnippet("multiParams", array("parent"=>"25", "action"=>"getParamsFromTree"));
 // где 25 - это id ресурса-родителя нужного списка в дереве, его подставляем самостоятельно
 // 
 
$out = '';
switch ($action){
    case 'getParamsToMultiTV' :
        $sql = "SELECT `id`,`caption` FROM " . $modx->getFullTableName('site_tmplvars') . " WHERE `category` IN (" . $param_cat_id . ") ORDER BY `rank` ASC, `caption` ASC";
        $q = $modx->db->query($sql);
        while($row = $modx->db->getRow($q)){
            $out .= '||' . $row['caption'] . '==' . $row['id'];
        }
        break;
    
    case 'getParamsFromTree' :
        $sql = "SELECT pagetitle, id FROM " . $modx->getFullTableName('site_content') . " WHERE parent={$parent} ORDER BY menuindex ASC, pagetitle ASC";
        $q = $modx->db->query($sql);
        while ($row = $modx->db->getRow($q)){
            $out .= '||' . $row['pagetitle'];
        }    
        break;
    
    default:
        $sql = "SELECT title, id FROM " . $modx->getFullTableName('list_value_table') . " WHERE parent={$parent} ORDER BY sort ASC, title ASC";
        $q = $modx->db->query($sql);
        while ($row = $modx->db->getRow($q)){
            $out .= '||' . $row['title'];
        }
        break;
}
return $out;
