<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

//список доступных форм
$formListTpl='
	<table class="fl">
		<thead>
			<tr>
				<td>id</td>
				<td>Имя</td>
				<td>Описание (вставить в поле "возможные значения" нужного TV)</td>
				<td>Значения</td>
				<td>Изменить</td>
				<td>Удалить</td>
			</tr>
		</thead>
		<tbody>
			[+formRows+]
		</tbody>
	</table>
	<br><br>
	<!--форма для создания новой формы-->
	<form action="" method="post" class="actionButtons">
		<input type="hidden" name="action" value="newForm">
		Новый параметр: <br><input type="text" value="" name="title"><br>
		<input type="submit" value="Добавить параметр">
	</form>
';

//строка формы в таблице списка форм
$formRowTpl='
	<tr>
		<td>[+id+]</td>
		<td><b>[+title+]</b></td>
		<td>[+code+]
 
<div class="modal" id="modal[+id+]">
   <div class="modal__info">
    <label class="modal__close" onclick="closeListTV([+id+])">&times;</label>
    <h3>Список TV</h3>
    <div id="tvs">[+tvList+]</div>
    <a href="[+moduleurl+]&fid=[+id+]&action=insertTv" id="insert[+id+]" class="btn btn-success">Вставить</a>
  </div>
</div>

<a href="#"  onclick="showListTV([+id+]); return false;" class="buttonModal">Вставить в TV</label>

    </td>
		<td class="actionButtons"><a href="[+moduleurl+]&fid=[+id+]&action=pole" class="button choice"> <img src="[+iconfolder+]page_white_copy.png" alt=""> Список значений</a></td>
		<td class="actionButtons"><a href="[+moduleurl+]&fid=[+id+]&action=edit" class="button edit"> <img alt="" src="[+iconfolder+]page_white_magnify.png" > Изменить</a></td>
		<td class="actionButtons"><a onclick="document.delform.delform1.value=[+id+];document.delform.submit();" style="cursor:pointer;" class="button delete"> <img src="[+iconfolder+]delete.png" alt=""> удалить</a></td>
	</tr>
';

//Вывод TVшки
$tvRowTpl ='<input type="radio" name="tvId" value="[+tvId+]">[+tvName+] – [+tvCaption+]<br/>';

$formEditTpl='
	<form action="" method="post" class="actionButtons">
		<input type="hidden" name="action" value="updateForm">
		Параметр: <br><input type="text" value=\'[+title+]\' name="title" size="50"><br>
		<input type="submit" value="Сохранить">
	</form><br><br>
	<a href="[+moduleurl+]">К списку параметров</a>
';

$fieldListTpl='
	<form id="sortpole" action="" method="post" class="actionButtons">
		<table class="fl">
			<thead>
				<tr>
					<td>Значение</td>
					<td>Порядок</td>
					<td>Изменить</td>
					<td>Удалить</td>
				</tr>
			</thead>
			<tbody>
				[+fieldRows+]
			</tbody>
		</table>
		<br>
		<input type="submit" value="Сохранить порядок">
	</form>
	<br><br>
	<h2>Добавление нового значения</h2>
	<form action="" method="post" class="actionButtons">
		<input type="hidden" name="action" value="newField">
		Название <br><input type="text" value="" name="title"><br>
		<input type="submit" value="Добавить значение">
	</form>
	<br><br>
	<a href="[+moduleurl+]">К списку параметров</a>
';

$fieldRowTpl='
		<tr>
			<td>[+title+]</td>
			<td><input type="text" name="sortpole[[+id+]]" value="[+sort+]" class="sort small"></td>
			<td> <a href="[+moduleurl+]&fid=[+parent+]&pid=[+id+]&action=pole" class="button edit"><img alt="" src="[+iconfolder+]page_white_magnify.png" > Изменить</a> </td>
			<td> <a onclick="document.delpole.delpole1.value=[+id+];document.delpole.submit();" style="cursor:pointer;" class="button delete"> <img src="[+iconfolder+]delete.png" alt=""> Удалить</a> </td>
		</tr>
';

$fieldEditTpl='
	<form action="" method="post" class="actionButtons">
		<input type="hidden" name="action" value="updateField">
		Значение: <br><input type="text" value=\'[+title+]\' name="title"><br>
		<input type="submit" value="Сохранить изменения">
	</form>
	<br><br>
	<a href="[+moduleurl+]&fid=[+parent+]&action=pole">К списку значений</a>
';








?>