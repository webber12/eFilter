<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
    'param_id' => array(
        'caption' => '<b>Параметр</b>',
        'type' => 'dropdown',
        'elements' => '@EVAL return $modx->runSnippet("multiParams", array("action"=>"getParamsToMultiTV"));'
    ),
    'fltr_name' => array(
        'caption' => 'Название фильтра',
        'type' => 'text'
    ),
    'fltr_type' => array(
        'caption' => 'Тип фильтра',
        'type' => 'dropdown',
        'elements' => '||Блок чекбоксов==checkbox||Одиночный чекбокс==simplecheckbox||Выпадающий список==option'
    ),
    'show_zagol' => array(
        'caption' => 'Заголовок',
        'type' => 'checkbox',
        'elements' => 'Нет==2'
    ),
    'show_href' => array(
        'caption' => 'Ссылка',
        'type' => 'checkbox',
        'elements' => 'Нет==2'
    ),
    'show_all' => array(
        'caption' => 'Показывать все',
        'type' => 'checkbox',
        'elements' => 'Нет==2'
    ),
    'new_line' => array(
        'caption' => 'Перенос строки после',
        'type' => 'checkbox',
        'elements' => 'Да==1'
    )
);
$settings['templates'] = array(
    'outerTpl' => '[+wrapper+]',
    'rowTpl' => '[+element+]'
);
$settings['configuration'] = array(
    'enablePaste' => false,
    'enableClear' => true
);
