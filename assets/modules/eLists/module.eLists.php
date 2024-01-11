<?php
/* author webber   web-ber12@yandex.ru */
// version 0.1
// визуальное создание и редактирование простых форм на основе сниппета eForm
// создать модуль с названием easyForm и кодом 
// require_once MODX_BASE_PATH."assets/modules/easyForm/module.easyForm.php";

if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

include_once('eLists.class.php');
$eL=new eListsModule($modx);
$eL->Run();
$csrf = '';
if(function_exists('csrf_field')) {
    $csrf = csrf_field();
}



/********************* шаблон вывода в модуль ************************/
$output=<<<OUT
<!doctype html>
<html lang="ru">
<head>
	<title>Управление параметрами товара</title>
	<link rel="stylesheet" type="text/css" href="media/style/{$eL->theme}/style.css" />
<style>
	table{width:100%;}
	table td{padding:2px 5px !important;border:solid 1px white;height:38px;vertical-align:middle !important;}
	table thead td{color:white;height:25px;
		background: none repeat scroll 0 0 #39515D;
		text-shadow: 0px -1px 0px #2B5F0C;
		padding:5px 5px  !important;
	}
	table tbody td{border-right: 1px solid #d4d4d4;border-bottom: 1px solid #d4d4d4;}
	input[type="text"]{width:300px;margin-bottom:5px !important;}
	select{width:307px;margin-bottom:5px !important;}
	input[type="text"].small{width:35px;}
	p.info{color:#008000;}
	p.error{color:#cc0000;}
</style>
</head>
<body>
	<h1>Управление списками</h1>
<div id="actions">
    <ul class="actionButtons">
        <li id="Button1"><a href="index.php?a=112&amp;id={$eL->moduleid}">
            <img src="media/style/{$eL->theme}/images/icons/refresh.png" alt="Обновить"/>
            Обновить
        </a></li>
        <li id="Button2"><a href="index.php?a=106">
            <img src="media/style/{$eL->theme}/images/icons/stop.png" alt="Закрыть"/>
            Закрыть
        </a></li>
    </ul>
</div>

<div class="sectionBody">
    <div class="dynamic-tab-pane-control tab-pane" id="multiTvPanes">
        <div class='tab-row'>
            <h2 id="tabs-event_log" class='tab selected'>{$eL->zagol}</h2>
            
        </div>
        <div class="tab-page panel-container" style="display:block !important;">
            <div id="tabpanel-event_log">
    <h2>{$eL->zagol}</h2>
	<div class="action_info">{$eL->info}</div>

		{$eL->eBlock}
				
		<form action="" method="post" id="delform" name="delform">
			{$csrf}
			<input type="hidden" name="delform1" value="">
		</form>
		<form action="" method="post" id="delpole" name="delpole">
			{$csrf}
			<input type="hidden" name="delpole1" value="">
		</form>
	</div>
</body>
</html>
OUT;

/****************** конец формирования шаблона в модуль ************/


//выводим все в область контента модуля
echo $output;
?>