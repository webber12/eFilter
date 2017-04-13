<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
$out = '';
$params = $modx->Event->params;
$add_config = array();
function _getParentParam ($docid, $param_tv_name) {
    global $modx;
    $filter_param = array();
    $parent = $modx->db->getValue("SELECT parent FROM " . $modx->getFullTableName('site_content') . " WHERE id = {$docid} AND parent != 0 LIMIT 0,1");
    if ($parent) {
        $param_tv_val = $modx->runSnippet("DocInfo", array('docid' => $parent, 'tv' => '1', 'field' => $param_tv_name));
        if ($param_tv_val != '' && $param_tv_val != '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}' && $param_tv_val != '[]') {
            $filter_param = $param_tv_val;
        }  else {
            $filter_param = _getParentParam ($parent, $param_tv_name);
        }
    }
    return $filter_param;
}
$docid = isset($docid) ? $docid : $modx->documentIdentifier;
$cfg = $modx->runSnippet("DocInfo", array("docid" => $docid, "field" => "filterparams", "tv" => "1"));
if ($cfg == '' || $cfg == '{"fieldValue":[{"param_id":""}],"fieldSettings":{"autoincrement":1}}') {
    $cfg = _getParentParam ($docid, 'filterparams');
}
if (isset($cfg) && !empty($cfg)) {
	$filter_config = json_decode($cfg, true);
	$add_config = array();
	foreach ($filter_config['fieldValue'] as $k => $v) {
		if (isset($v['param_id']) && !empty($v['param_id']) && isset($v['is_fltr']) && $v['is_fltr'] == '1') {
			$add_config[$v['param_id']] = array(
				'fltr_name' => $v['fltr_name'],
				'fltr_type' => !empty($v['fltr_type']) ? $v['fltr_type'] : 'checkbox',
				'show_zagol' => $v['show_zagol'],
				'show_href' => $v['show_href'],
				'show_all' => $v['show_all'],
				'new_line' => $v['new_line']
			);
			$snip_params = array_merge($params, array('tvs' => $v['param_id'], 'add_config' => $add_config));
			$out .= $modx->runSnippet("makeFilter", $snip_params);
		}
	}
	//print_r($add_config);
	if (!empty($add_config)) {
		//$out .= $modx->runSnippet("makeFilter", array('add_config' => $add_config));
	}
	return $out;
}
return $out;
?>