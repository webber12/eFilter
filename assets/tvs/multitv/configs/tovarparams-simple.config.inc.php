<?php
$settings['display'] = 'horizontal';
$settings['fields'] = array(
	'param_id' => array(
        'caption' => '<b>Параметр</b>',
        'type' => 'dropdown',
        'elements' => '@EVAL return $modx->runSnippet("multiParams", array("action"=>"getParamsToMultiTV"));'
    )
);
$settings['templates'] = array(
    'outerTpl' => '[+wrapper+]',
    'rowTpl' => '[+element+]'
);
$settings['configuration'] = array(
    'enablePaste' => false,
    'enableClear' => true
);
