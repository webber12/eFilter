<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
include_once('makeFilter.class.php');
$filter = new makeFilter($modx, $params);
$out = $filter->run();
return $out;
?>