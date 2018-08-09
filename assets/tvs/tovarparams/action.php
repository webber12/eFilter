<?php

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once(__DIR__ . "/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}


if (!isset($_SESSION['mgrValidated'])) {
    die();
}

//$modx->logEvent(1,1,print_r($_REQUEST, true), '_REQUEST');

//начинаем...
$out = '';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$docid = isset($_REQUEST['docid']) && (int)$_REQUEST['docid'] > 0 ? (int)$_REQUEST['docid'] : 0;
$tvid = isset($_REQUEST['tvid']) && (int)$_REQUEST['tvid'] > 0 ? (int)$_REQUEST['tvid'] : 0;
$defaults = array('param_id' => '9', 'cat_name' => '', 'list_yes' => '0', 'fltr_yes' => '1', 'fltr_type' => '1', 'fltr_name' => '', 'fltr_many' => '0', 'fltr_href' => '0');
$fields = array_keys($defaults);
switch($action) {
    case 'update':
        $arr = array();
        foreach ($fields as $field) {
            if (isset($_REQUEST[$field])) {
                $arr[$field] = $_REQUEST[$field];
            }
        }
        $opetarion = isset($_REQUEST['webix_operation']) ? $_REQUEST['webix_operation'] : '';
        switch ($opetarion) {
            case 'update':
                if(!$docid || !$tvid) break;
                $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
                $curr = json_decode($tmp['value'], true);
                $iteration = (int)$_REQUEST['id'] - 1;
                if (isset($curr['fieldValue'])) {
                    $tmp2 = $curr['fieldValue'];
                    foreach ($tmp2 as $k => $v) {
                        if ($k == $iteration) {
                            $curr['fieldValue'][$k] = $arr;
                        }
                    }
                }
                //$modx->logEvent(1,1,print_r($curr, true), 'curr');
                $modx->db->update(array('value' => $modx->db->escape(json_encode($curr))), $modx->getFullTableName("site_tmplvar_contentvalues"), "contentid={$docid} AND tmplvarid={$tvid}");
                break;
            case 'insert':
                if (!empty($arr) && isset($arr[$idField]) && $arr[$idField] != '') {
                    $modx->db->insert($arr, $modx->getFullTableName($table));
                } else if ($idField == 'id') {
                    $max = $modx->db->getValue("SELECT MAX(`" . $idField . "`) FROM " . $modx->getFullTableName($table));
                    $max = $max ? ($max + 1) : 1;
                    $modx->db->insert(array('id' => $max), $modx->getFullTableName($table));
                }
                break;
            case 'delete':
                if (!empty($arr) && isset($arr[$idField]) && $arr[$idField] != '') {
                    $modx->db->delete($modx->getFullTableName($table), "`" . $idField . "`='" . $arr[$idField] . "'");
                }
                break;
        }
        break; 

    case 'list':
        $docid = isset($_REQUEST['docid']) && (int)$_REQUEST['docid'] > 0 ? (int)$_REQUEST['docid'] : 0;
        $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
        $arr = json_decode($tmp['value'], true);
        $rows = array();
        $total = 0;
        foreach ($arr['fieldValue'] as $k => $v) {
            $rows[$k] = $v;
            $rows[$k]['id'] = $k + 1;
            $total ++;
        }
        $out = json_encode(array("data" => $rows, "pos" => 0, "total_count" => $total));
        //$modx->logEvent(1,1,$out, 'out');
        break;

    case 'get_tv_value':
        $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
        $out = $tmp["value"];
        break;

    case 'get_tv_list':
        $out = $modx->runSnippet("multiParams", array("action" => "getParamsToWebix"));
        //$modx->logEvent(1,1,$out,'select');
        break;

    case 'save_order':
        //$modx->logEvent(1,1,$_REQUEST['order'], 'sort dnd');
        $new_order = array_map("trim", explode(',', $_REQUEST['order']));
        $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
        $curr = json_decode($tmp['value'], true);
        //$modx->logEvent(1,1,print_r($curr, true), 'curr before dnd');
        $iteration = (int)$_REQUEST['id'] - 1;
        $new = array();
        if (isset($curr['fieldValue'])) {
            foreach ($new_order as $v) {
                $iteration = $v - 1;
                if (isset($curr['fieldValue'][$iteration])) {
                    $new[] = $curr['fieldValue'][$iteration];
                }
            }
        }
        //$modx->logEvent(1,1,print_r($new, true), 'curr after dnd');
        $curr['fieldValue'] = $new;
        $modx->db->update(array('value' => $modx->db->escape(json_encode($curr))), $modx->getFullTableName("site_tmplvar_contentvalues"), "contentid={$docid} AND tmplvarid={$tvid}");
        break;

    case 'add_row':
        $docid = isset($_REQUEST['docid']) && (int)$_REQUEST['docid'] > 0 ? (int)$_REQUEST['docid'] : 0;
        $tvid = isset($_REQUEST['tvid']) && (int)$_REQUEST['tvid'] > 0 ? (int)$_REQUEST['tvid'] : 0;
        $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
        $curr = json_decode($tmp['value'], true);
        $num = isset($_REQUEST['num']) ? (int)$_REQUEST['num'] : 0;
        $new = array();
        if ($num > 0) {
            $num = $num - 1;
        }
        if (isset($curr['fieldValue']) && is_array($curr['fieldValue']) && !empty($curr['fieldValue'])) {//уже что-то есть
            $tmp = array();
            foreach ($curr['fieldValue'] as $k => $v) {
                if ($k == $num) {
                    $tmp[] = $v;
                    $tmp[] = $defaults;
                } else {
                    $tmp[] = $v;
                }
            }
            $new = array('fieldValue' => $tmp, 'autoincrement' => '1');
        } else {//еще ничего нет
            $new = array('fieldValue' => array('0' => $defaults), 'autoincrement' => '1');
        }
        
        if (!empty($new)) {
            $value = $modx->db->escape(json_encode($new));
            $modx->db->query("INSERT INTO " . $modx->getFullTableName("site_tmplvar_contentvalues") . " (`contentid`,`tmplvarid`,`value`) VALUES ('" . $docid . "','" . $tvid . "','" . $value . "') ON DUPLICATE KEY UPDATE `value` = '" . $value . "'");
        }
        $out = json_encode($defaults);
        break;

    case 'del_row':
        $docid = isset($_REQUEST['docid']) && (int)$_REQUEST['docid'] > 0 ? (int)$_REQUEST['docid'] : 0;
        $tvid = isset($_REQUEST['tvid']) && (int)$_REQUEST['tvid'] > 0 ? (int)$_REQUEST['tvid'] : 0;
        $tmp = $modx->getTemplateVar("tovarparams", "*", $docid, "all");
        $curr = json_decode($tmp['value'], true);
        $num = isset($_REQUEST['num']) ? (int)$_REQUEST['num'] : 0;
        $new = array();
        if ($num > 0) {
            $num = $num - 1;
        }
        if (isset($curr['fieldValue']) && is_array($curr['fieldValue']) && !empty($curr['fieldValue'])) {//уже что-то есть
            $tmp = array();
            foreach ($curr['fieldValue'] as $k => $v) {
                if ($k != $num) {
                    $tmp[] = $v;
                }
            }
            if (!empty($tmp)) {
                $curr['fieldValue'] = $tmp;
                $value = $modx->db->escape(json_encode($curr));
                //если значения есть - обновляем
                $modx->db->query("INSERT INTO " . $modx->getFullTableName("site_tmplvar_contentvalues") . " (`contentid`,`tmplvarid`,`value`) VALUES ('" . $docid . "','" . $tvid . "','" . $value . "') ON DUPLICATE KEY UPDATE `value` = '" . $value . "'");
            } else {
                //если значений не осталось - удаляем все
                $modx->db->query("DELETE FROM " . $modx->getFullTableName("site_tmplvar_contentvalues") . " WHERE contentid={$docid} AND tmplvarid={$tvid}");
            }
        }
        $out = $value;
        break;

    case 'remove':
        $docid = isset($_REQUEST['docid']) && (int)$_REQUEST['docid'] > 0 ? (int)$_REQUEST['docid'] : 0;
        $tvid = isset($_REQUEST['tvid']) && (int)$_REQUEST['tvid'] > 0 ? (int)$_REQUEST['tvid'] : 0;
        $modx->db->query("DELETE FROM " . $modx->getFullTableName("site_tmplvar_contentvalues") . " WHERE contentid={$docid} AND tmplvarid={$tvid}");
        $out = '';
        break;

    default:
        break;
}
//$modx->logEvent(1,1,$out, 'out2');
echo $out;
