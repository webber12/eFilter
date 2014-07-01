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
