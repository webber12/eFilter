<?php

$path = __DIR__ . '/../../../';

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', $path);
define('MODX_SITE_URL', 'https://www.sitex.by/');
define('MODX_BASE_URL', 'https://www.sitex.by/');
include_once($path . 'index.php');

if (empty($modx->config)) {
    $modx->getSettings();
}

//dd($_SESSION);

if(!is_cli() && empty($_SESSION['mgrValidated'])) {
    evo()->logEvent(3, 1, 'use cli to run script or in manager mode', 'cron.php error');
    die('use cli to run script or in manager mode');
}

if(is_cli()) {
    parse_str($argv[1], $params);
} else {
    $params = $_GET;
}
$action = !empty($params['action']) ? $params['action'] : '';
$ttl = $params['ttl'] ?? 0;

switch($action) {
    case 'index':
    default:
        evo()->logEvent(1,1, 'iFilter index', 'iFilter index start');
        evo()->runSnippet('iFilter', [ 'action' => 'index', 'ttl' => $ttl ]);
        $count = evo()->getPlaceholder('iFilterIndexed');
        if(!empty($count)) {
            evo()->logEvent(1,1, 'iFilter indexed tv: ' . $count, 'iFilter index finish');
        }
}

exit;


