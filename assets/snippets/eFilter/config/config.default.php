<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

//общая форма фильтра
$tplFilterForm = '<form id="eFiltr" class="eFiltr eFiltr_form" action="[+url+]" method="get">[+wrapper+]</form>';


//название категории фильтра
$filterCatName = '<div class="fltr_cat_zagol">[+cat_name+]</div>';


//чекбоксы
$tplRowCheckbox = '
	<label class="[+disabled+]">
		<input type="checkbox" name="f[[+tv_id+]][]" value="[+value+]" [+selected+] [+disabled+]> [+name+] <span class="fltr_count">[+count+]</span>
	</label>
';
$tplOuterCheckbox = '
	<div class="fltr_block fltr_block_checkbox fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_checkbox fltr_name[+tv_id+]">[+name+]</span>
		[+wrapper+]
	</div>
';


//выпадающий список - селект
$tplRowSelect = '<option value="[+value+]" [+selected+] [+disabled+]>[+name+] ([+count+])</option>';
$tplOuterSelect = '
	<div class="fltr_block fltr_block_select fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_select fltr_name[+tv_id+]">[+name+]</span>
		<select name="f[[+tv_id+]][]">
			<option value="0"> - [+name+] - </option>
			[+wrapper+]
		</select>
	</div>
';


//диапазон
$tplRowInterval = 'от<input type="text" name="f[[+tv_id+]][min]" value="[+minval+]" data-min-val="[+minvalcurr+]"> до <input type="text" name="f[[+tv_id+]][max]" value="[+maxval+]" data-max-val="[+maxvalcurr+]">';
$tplOuterInterval = '
	<div class="fltr_block fltr_block_interval fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_interval fltr_name[+tv_id+]">[+name+]</span>
		[+wrapper+]
	</div>
';


//радио - radio 
$tplRowRadio = '<input type="radio" name="f[[+tv_id+]][]" value="[+value+]" [+selected+] [+disabled+]> [+name+] <span class="fltr_count">[+count+]</span>';
$tplOuterRadio = '
	<div class="fltr_block fltr_block_radio fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_radio fltr_name[+tv_id+]">[+name+]</span>
		<input type="radio" name="f[[+tv_id+]][]" value="0"> Все</span>
		[+wrapper+]
	</div>
';

//выпадающий список - мультиселект
$tplRowMultySelect = '<option value="[+value+]" [+selected+] [+disabled+]>[+name+] ([+count+])</option>';
$tplOuterMultySelect = '
	<div class="fltr_block fltr_block_multy fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_multy fltr_name[+tv_id+]">[+name+]</span>
		<select name="f[[+tv_id+]][]" multiple size="5">
			<option value="0"> - [+name+] - </option>
			[+wrapper+]
		</select>
	</div>
';

//слайдер
$tplRowSlider = '<div style="display:none;">от<input type="text" id="minCostInp[+tv_id+]" name="f[[+tv_id+]][min]" value="[+minval+]" data-min-val="[+minvalcurr+]"> до <input type="text" id="maxCostInp[+tv_id+]" name="f[[+tv_id+]][max]" value="[+maxval+]" data-max-val="[+maxvalcurr+]"></div>';
$tplOuterSlider = '
	<div class="fltr_block fltr_block_slider fltr_block[+tv_id+]">
		<span class="fltr_name fltr_name_slider fltr_name[+tv_id+]">[+name+]</span>
		<div class="fltr_inner fltr_inner_slider fltr_inner[+tv_id+]">
		<div class="slider_text slider_text[+tv_id+]">от <span id="minCost[+tv_id+]"></span> до <span id="maxCost[+tv_id+]"></span></div>
		<div id="slider[+tv_id+]"></div>
		[+wrapper+]
		</div>
	</div>
	<script type="text/javascript">
	
$(document).ready(function(){
var minCost = 0;
var maxCost = 0;
var minCostCurr = 0;
var maxCostCurr = 0;
if ($("#minCostInp[+tv_id+]").val() != "") {
	minCostCurr = $("#minCostInp[+tv_id+]").val();
} else {
	minCostCurr = $("#minCostInp[+tv_id+]").data("minVal");
}
if ($("#maxCostInp[+tv_id+]").val() != "") {
	maxCostCurr = $("#maxCostInp[+tv_id+]").val();
} else {
	maxCostCurr = $("#maxCostInp[+tv_id+]").data("maxVal");
}
minCost = $("#minCostInp[+tv_id+]").data("minVal");
maxCost = $("#maxCostInp[+tv_id+]").data("maxVal");
$("#minCost[+tv_id+]").html(minCostCurr);
$("#maxCost[+tv_id+]").html(maxCostCurr);
$("#slider[+tv_id+]").slider({
	min: minCost,
	max: maxCost,
	values: [minCostCurr,maxCostCurr],
	range: true,
	stop: function(event, ui) {
		$("input#minCostInp[+tv_id+]").val($("#slider[+tv_id+]").slider("values",0));
		$("input#maxCostInp[+tv_id+]").val($("#slider[+tv_id+]").slider("values",1));
		$("#minCost[+tv_id+]").text($("#slider[+tv_id+]").slider("values",0));
		$("#maxCost[+tv_id+]").text($("#slider[+tv_id+]").slider("values",1));
		$("input#minCostInp[+tv_id+]").change();
    },
    slide: function(event, ui){
		$("input#minCostInp[+tv_id+]").val($("#slider[+tv_id+]").slider("values",0));
		$("input#maxCostInp[+tv_id+]").val($("#slider[+tv_id+]").slider("values",1));
		$("#minCost[+tv_id+]").text(jQuery("#slider[+tv_id+]").slider("values",0));
		$("#maxCost[+tv_id+]").text(jQuery("#slider[+tv_id+]").slider("values",1));
    }
});
});
</script>
';

