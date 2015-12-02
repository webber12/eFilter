<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
$out = '';
$params = $modx->Event->params;
if (isset($cfg) && !empty($cfg)) {
	$filter_config = json_decode($cfg, true);
	$add_config = array();
	foreach ($filter_config['fieldValue'] as $k => $v) {
		if (isset($v['param_id']) && !empty($v['param_id'])) {
			$add_config[$v['param_id']] = array(
				'fltr_name' => $v['fltr_name'],
				'fltr_type' => $v['fltr_type'],
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