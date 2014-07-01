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
		border: 1px solid #658f1a;
		background: none repeat scroll 0 0 #66901b;
		text-shadow: 0px -1px 0px #2B5F0C;
		border-radius:5px 5px 0 0;
		-moz-border-radius:5px 5px 0 0;
		-webkit-border-radius:5px 5px 0 0;
		-ms-border-radius:0;
		background:-moz-linear-gradient(#8aae4b, #66901b);
		background:-webkit-gradient(linear, 0 0, 0 100%, from(#8aae4b), to(#66901b));
		background:-o-linear-gradient(#8aae4b, #66901b);
	}
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
        <div class="tab-page panel-container">
            <div id="tabpanel-event_log">
    <h2>{$eL->zagol}</h2>
	<div class="action_info">{$eL->info}</div>

		{$eL->eBlock}
				
		<form action="" method="post" id="delform" name="delform"> 
			<input type="hidden" name="delform1" value="">
		</form>
		<form action="" method="post" id="delpole" name="delpole"> 
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