<?php

if (!IN_MANAGER_MODE) {
    die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

include_once(MODX_BASE_PATH . 'assets/tvs/tovarparams/lib/tovarparams.class.php');
$tovarparams = new \Tovarparams\Tovarparams ($modx, $row);

echo $tovarparams->render();