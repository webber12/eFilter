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
	label {
    cursor: pointer;
    user-select: none;
}

.buttonModal {
    display: inline-block;
    padding: 10px;
    text-transform: uppercase;
    
    text-align: center;
    color: #fff;
    background-color: #2c2c2c;
    -webkit-transition: all .3s;
    -o-transition: all .3s;
    transition: all .3s;
}
.buttonModal:hover{
    color: #111;
    background-color: #dcdcdc;
    text-decoration: none;
}
.modal {
    position: fixed;
    z-index: -10;
    top: 0;
    right: 100%;
    bottom: 0;
    width: 100%;
    display: flex !important;
    align-items: center;
    justify-content: center;
    background-color: rgba(0,0,0,.2);
    opacity: 0;
    transition: opacity .3s;
}


.modal__info {
    position: relative;
    width: 90%;
    max-width: 400px;
    max-height: 90%;
    padding: 20px 20px 5px;
    background-color: #fff;
    overflow: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.modal__close {
    font-family: serif;
    position: absolute;
    z-index: 2;
    top: 5px;
    right: 5px;
    width: 25px;
    border-radius: 50%;
    font-size: 36px;
    line-height: 25px;
    text-align: center;
}

.modal.show {
	z-index: 1;
    opacity: 1;
    right: 0;
}

.button:hover,.modal__close:hover {opacity: 0.7;}
.modal__info::-webkit-scrollbar {display: none;}
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
			<input type="hidden" name="delform1" value="">
		</form>
		<form action="" method="post" id="delpole" name="delpole">
			<input type="hidden" name="delpole1" value="">
		</form>
	</div>
</body>
<script>
	var tvId, link,tmpLink;
	var radios = document.getElementsByName('tvId');
	var ch_radio=function()
	{
        link = this.parentElement.nextElementSibling;
        l = link.href.indexOf('&tvId=');
        tmpLink = link.href;
        if(l!=-1) tmpLink = link.href.substring(0,l);
		tvId = this.value;
		link.href = tmpLink +'&tvId='+tvId;
	}
	for(var i=0;i<radios.length;i++) radios[i].onchange=ch_radio;

    function showListTV(id){
        var el = document.getElementById("modal"+id);
        el.classList.add("show");
    }

    function closeListTV(id){
        var el = document.getElementById("modal"+id);
        el.classList.remove("show");
    }
</script>
</html>
OUT;

/****************** конец формирования шаблона в модуль ************/


//выводим все в область контента модуля
echo $output;
?>