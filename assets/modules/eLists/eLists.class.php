<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

class eListsModule{

public $moduleid;
public $moduleurl;
public $iconfolder;
public $theme;
public $info_type=1;
public $eBlock=''; //тут будет главный инфоблок в зависимости от значения $info_type
public $info=''; //информационная надпись в случае удачного/неудачного действия
public $zagol='Список параметров';
public $list_catagory_table='';
public $list_value_table='';
public $type=array(//доступные типы полей в форме
				"1"=>"Строка",
				"2"=>"Текст",
				"3"=>"Email",
				"5"=>"Список (select)",
				"6" =>"Флажок (radio)",
				"7"=>"Переключатель (checkbox)",
				"8"=>"Файл",
				"9"=>"Мультиселект",
				"10"=>"Скрытое поле hidden"
			);
public $form_info;
public $pole_info;
			

public function __construct($modx){
	$this->modx=$modx;
	$this->moduleid=(int)$_GET['id'];
	$this->moduleurl='index.php?a=112&id='.$this->moduleid;
	$this->list_catagory_table=$this->modx->getFullTableName('list_catagory_table');
	$this->list_value_table=$this->modx->getFullTableName('list_value_table');
	$this->theme=$this->modx->config['manager_theme'];
	$this->iconfolder='media/style/'.$this->theme.'/images/icons/';
}

public function parseTpl($arr1, $arr2, $tpl){
	$csrf = '';
	if(function_exists('csrf_field')) {
		$csrf = csrf_field();
	}
	$arr1[] = '[+csrf+]';
	$arr2[] = $csrf;
	return str_replace($arr1,$arr2,$tpl);
}

public function createTables(){
	//создаем таблицу форм, если ее нет
	$sql="
	CREATE TABLE IF NOT EXISTS " . $this->list_catagory_table . " (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`sort` int(5) NOT NULL DEFAULT '0',
		`title` text NOT NULL DEFAULT '',
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$q=$this->modx->db->query($sql);

	//создаем таблицу полей форм, если ее нет
	$sql="
	CREATE TABLE IF NOT EXISTS " . $this->list_value_table . " (
		`id` int(5) NOT NULL AUTO_INCREMENT,
		`parent` int(5) NOT NULL DEFAULT '0',
		`title` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) DEFAULT NULL,
		`sort` int(5) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";
	$q=$this->modx->db->query($sql);
}


public function escape($a){
	return $this->modx->db->escape($a);
}

public function getRow($table,$id){
	$row=$this->modx->db->getRow($this->modx->db->query("SELECT * FROM ".$table." WHERE id=".$id." LIMIT 0,1"));
	return $row;
}

public function addForm($fields,$table){
	$query=$this->modx->db->insert($fields,$table);
	if($query){$this->info='<p class="info">Параметр успешно добавлен</p>';}
	else{$this->info='<p class="info error">Не удалось добавить параметр</p>';}
}

public function updateForm($fields,$table,$where){
	$query=$this->modx->db->update($fields,$table,$where);
	if($query){$this->info='<p class="info">Параметр успешно изменен</p>';}
	else{$this->info='<p class="info error">Не удалось изменить параметр</p>';}
}

public function delForm($id){
	$query=$this->modx->db->query("DELETE FROM ".$this->list_catagory_table." WHERE id=".$id);
	if($query){
		$query2=$this->modx->db->query("DELETE FROM ".$this->list_value_table." WHERE parent=".$id);
		$this->info='<p class="info">Параметр успешно удален</p>';
	}
	else{$this->info='<p class="info error">Не удалось удалить параметр</p>';}
}


public function addField($fields, $table){
	$query=$this->modx->db->insert($fields, $table);
	if($query){$this->info='<p class="info">Значение списка успешно добавлено</p>';}
	else{$this->info='<p class="info error">Не удалось добавить значение</p>';}
}

public function updateField($fields,$table,$where){
	$query=$this->modx->db->update($fields,$table,$where);
	if($query){$this->info='<p class="info">Значение успешно изменено</p>';}
	else{$this->info='<p class="info error">Не удалось изменить значение</p>';}
}

public function sortFields($order){
	$ok=1;
	foreach($order as $k=>$v){//сохраняем порядок сортировки
		$query=$this->modx->db->query("UPDATE ".$this->list_value_table." SET `sort`='".(int)$v."' WHERE id=".(int)$k);
		if(!$query){$ok=1;}
	}
	if($ok==1){$this->info='<p class="info">Значения успешно отсортированы</p>';}
	else{$this->info='<p class="info error">Ошибка при изменении порядка полей</p>';}
}

public function delField($id){
	$query=$this->modx->db->query("DELETE FROM ".$this->list_value_table." WHERE id=".$id);
	if($query){$this->info='<p class="info">Значение успешно удалено</p>';}
	else{$this->info='<p class="info error">Не удалось удалить значение</p>';}
}



public function makeActions(){
	if(isset($_POST['delform1'])){//удаление формы
		$this->delForm((int)$_POST['delform1']);
	}
	
	if(isset($_POST['delpole1'])){//удаление поля
		$this->delField((int)$_POST['delpole1']);
	}

	if(isset($_POST['action'])&&$_POST['action']=='newForm'){//добавляем новую форму
		$name=$this->escape($_POST['name']);
		$title=$this->escape($_POST['title']);
		$email=$this->escape($_POST['email']);
		$sort=1;
		$maxformsort=$this->modx->db->getValue($this->modx->db->query("SELECT MAX(sort) FROM ".$this->list_catagory_table." LIMIT 0,1"));
		if($maxformsort){
			$sort=(int)$maxformsort+1;
		}
		$flds=array(
			'title'=>$title,
			'sort'=>$sort
		);
		$this->addForm($flds,$this->list_catagory_table);
	}

	if(isset($_GET['fid'])&&isset($_GET['action'])&&$_GET['action']=='edit'){//редактирование формы
		$this->info_type=2;
		$this->zagol='Редактирование параметра';
		if(isset($_POST['action'])&&$_POST['action']=='updateForm'){
			$title=$this->escape($_POST['title']);
			$flds=array(
				'title'=>$title
			);
			
			$this->updateForm($flds, $this->list_catagory_table, "id=" . (int)$_GET['fid']);
		}
		
		//обновляем информацию о форме для вывода
		$this->form_info=$this->getRow($this->list_catagory_table, (int)$_GET['fid']);
	}


	//список полей формы
	if(isset($_GET['fid'])&&isset($_GET['action'])&&$_GET['action']=='pole'&&!isset($_GET['pid'])){
		$this->info_type=3;
		$this->zagol='Список значений параметра';
		
		if(isset($_POST['sortpole'])){//сортируем поля
		
			$this->sortFields($_POST['sortpole']);
		}
		
		$parent=(int)$_GET['fid'];
		if(isset($_POST['action'])&&$_POST['action']=='newField'){//добавляем новое поле
			$title = $this->escape($_POST['title']);
            $value = $this->escape($_POST['value']);
            if(!empty($value)) {
                $title .= '==' . $value;
            }
            $description = $this->escape($_POST['description']);
			$type=$this->escape($_POST['type']);
			$value=$this->escape($_POST['value']);
			$require=isset($_POST['require'])?1:0;
			$sort = 1;
			$maxpolesort = $this->modx->db->getValue($this->modx->db->query("SELECT MAX(sort) FROM ".$this->list_value_table." WHERE parent=".$parent." LIMIT 0,1"));
			if($maxpolesort){
				$sort = (int)$maxpolesort+1;
			}
			$fields = array(
				'parent' => $parent,
				'title' => $title,
                'description' => $description,
				'sort' => $sort
			);
			$this->addField($fields, $this->list_value_table);
		}
	}//конец список полей


	//редактирование поля формы
	if(isset($_GET['fid'])&&isset($_GET['action'])&&$_GET['action']=='pole'&&isset($_GET['pid'])){
		$this->info_type = 4;
		$this->zagol = 'Редактирование значения';
		$parent=(int)$_GET['fid'];
		if(isset($_POST['action']) && $_POST['action'] == 'updateField'){//редактируем поле
			$title = $this->escape($_POST['title']);
            $value = $this->escape($_POST['value']);
            if(!empty($value)) {
                $title .= '==' . $value;
            }
            $description = $this->escape($_POST['description']);
			//$type=$this->escape($_POST['type']);
			//$require=isset($_POST['require'])?1:0;
			$fields = array(
				'title' => $title,
                'description' => $description
			);
			
			$this->updateField($fields, $this->list_value_table,"id=" . (int)$_GET['pid']);
		}
		
		//обновляем информацию о поле для вывода
		$this->pole_info=$this->getRow($this->list_value_table,(int)$_GET['pid']);
	}
}


public function getFormList(){
	include_once('config/config.php');
	$form_list=$this->modx->db->query("SELECT * FROM ".$this->list_catagory_table." ORDER BY sort ASC");
	$formRows='';
	$out='';
	while($row=$this->modx->db->getRow($form_list)){
		$formRows.=$this->parseTpl(
			array('[+id+]', '[+title+]', '[+moduleurl+]', '[+iconfolder+]', '[+code+]'),
			array($row['id'], $row['title'], $this->moduleurl, $this->iconfolder, '@EVAL return $modx->runSnippet("multiParams", array("parent"=>"' . $row['id'] . '"));'),
			$formRowTpl
		);
	}
	$out=$this->parseTpl(
				array('[+formRows+]'),
				array($formRows),
				$formListTpl
				);
	return $out;
}

public function getFormEdit(){
	include_once('config/config.php');
	$out='';
	$out.=$this->parseTpl(
		array('[+title+]', '[+moduleurl+]'),
		array($this->form_info['title'], $this->moduleurl),
		$formEditTpl
	);
	return $out;
}

public function getFieldList(){
	include_once('config/config.php');
	$out='';
	$rows='';
	$form_list=$this->modx->db->query("SELECT * FROM ".$this->list_value_table." WHERE parent=".(int)$_GET['fid']." ORDER BY sort ASC");
	while($row=$this->modx->db->getRow($form_list)){
		$rows.=$this->parseTpl(
			array('[+id+]', '[+parent+]', '[+title+]', '[+sort+]', '[+description+]', '[+moduleurl+]', '[+iconfolder+]'),
			array($row['id'], $row['parent'], $row['title'], $row['sort'], $row['description'], $this->moduleurl, $this->iconfolder),
			$fieldRowTpl
		);
	}

	$out=$this->parseTpl(
		array('[+fieldRows+]','[+moduleurl+]'),
		array($rows,$this->moduleurl),
		$fieldListTpl
	);
	return $out;
}

public function getFieldEdit(){
	include_once('config/config.php');
    $tmp = explode('==', $this->pole_info['title'], 2);
    $title = $tmp[0];
    $value = $tmp[1] ?? '';
	$this->eBlock .= $this->parseTpl(
		array('[+title+]', '[+value+]', '[+description+]', '[+parent+]', '[+moduleurl+]'),
		array($title, $value, (string)$this->pole_info['description'], $this->pole_info['parent'], $this->moduleurl),
		$fieldEditTpl
	);
	return $out;
}

public function show(){
	//блок вывода списка форм
	switch ($this->info_type){
		case '1':
			$this->eBlock .= $this->getFormList();
		break;
		
		case '2':
			$this->eBlock .= $this->getFormEdit();
		break;
		
		case '3':
			$this->eBlock .= $this->getFieldList();
		break;
		
		case '4':
			$this->eBlock .= $this->getFieldEdit();
		break;
		
		default:
			$this->eBlock .= $this->getFormList();
		break;		
	}
}


public function Run(){
	$this->createTables();
	$this->makeActions();
	$this->show();
}

}
//