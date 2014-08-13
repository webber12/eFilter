<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

//общая форма фильтра
$tplFilterForm = '<form id="eFiltr" class="eFiltr eFiltr_form" action="[+url+]" method="get">[+wrapper+]</form>';


//название категории фильтра
$filterCatName = '<div class="fltr_cat_zagol">[+cat_name+]</div>';


//чекбоксы
$tplRowCheckbox = '
	<label class="[+disabled+]">
		<input type="checkbox" name="f[[+tv_id+]][]" value="[+value+]" [+selected+] [+disabled+]  onchange="document.getElementById(\'eFiltr\').submit();"> [+value+] <span class="fltr_count">[+count+]</span>
	</label>
';
$tplOuterCheckbox = '
	<div class="fltr_block fltr_block_checkbox fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_checkbox fltr_name[+tv_id+]">[+name+]</span>
		[+wrapper+]
	</div>
';


//выпадающий список - селект
$tplRowSelect = '<option value="[+value+]" [+selected+] [+disabled+]>[+value+] ([+count+])</option>';
$tplOuterSelect = '
	<div class="fltr_block fltr_block_select fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_select fltr_name[+tv_id+]">[+name+]</span>
		<select name="f[[+tv_id+]][]" onchange="document.getElementById(\'eFiltr\').submit();">
			<option value="0"> - [+name+] - </option>
			[+wrapper+]
		</select>
	</div>
';


//диапазон
$tplRowInterval = 'от<input type="text" name="f[[+tv_id+]][min]" value="[+minval+]" data-min-val="[+minvalcurr+]" onblur="document.getElementById(\'eFiltr\').submit();"> до <input type="text" name="f[[+tv_id+]][max]" value="[+maxval+]" data-max-val="[+maxvalcurr+]" onblur="document.getElementById(\'eFiltr\').submit();">';
$tplOuterInterval = '
	<div class="fltr_block fltr_block_interval fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_interval fltr_name[+tv_id+]">[+name+]</span>
		[+wrapper+]
	</div>
';


//радио - radio 
$tplRowRadio = '<input type="radio" name="f[[+tv_id+]][]" value="[+value+]" [+selected+] [+disabled+]  onchange="document.getElementById(\'eFiltr\').submit();"> [+value+] <span class="fltr_count">[+count+]</span>';
$tplOuterRadio = '
	<div class="fltr_block fltr_block_select fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_select fltr_name[+tv_id+]">[+name+]</span>
		<input type="radio" name="f[[+tv_id+]][]" value="0" onchange="document.getElementById(\'eFiltr\').submit();" checked="checked"> Все</span>
		[+wrapper+]
	</div>
';

//выпадающий список - мультиселект
$tplRowMultySelect = '<option value="[+value+]" [+selected+] [+disabled+]>[+value+] ([+count+])</option>';
$tplOuterMultySelect = '
	<div class="fltr_block fltr_block_select fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_select fltr_name[+tv_id+]">[+name+]</span>
		<select name="f[[+tv_id+]][]" onchange="document.getElementById(\'eFiltr\').submit();" multiple size="5">
			<option value="0"> - [+name+] - </option>
			[+wrapper+]
		</select>
	</div>
';



