<?php
if(empty($params['docid'])) {
    if(!empty($modx->documentIdentifier)) {
        $params['docid'] = $modx->documentIdentifier;
    } else {
        $params['docid'] = $modx->getConfig('site_start');
    }
}
if(empty($params['parents'])) {
    $params['parents'] = $params['docid'];
}
$controllerName = 'Controller';
$path = __DIR__ . '/src/';
if(!empty($params['controller']) && is_file($path . 'Controllers/' . $params['controller'] . '.php')) {
    include_once $path . 'Controllers/' . $params['controller'] . '.php';
    $controllerName = $params['controller'];
} else {
    include_once $path . 'Controllers/' . $controllerName . '.php';
}
$controllerName = '\\iFilter\\' . $controllerName;

//$params['action'] = 'index';

$result = (new $controllerName($modx, $params))->process();

return $result;
