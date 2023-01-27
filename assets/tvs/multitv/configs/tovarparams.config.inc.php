<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
    'param_id' => array(
        'caption' => '<b>Параметр</b>',
        'type' => 'dropdown',
        'elements' => '@EVAL return $modx->runSnippet("multiParams", array("action"=>"getParamsToMultiTV"));'
    ),
    'cat_name' => array(
        'caption' => 'Категория',
        'type' => 'text'
    ),
    'list_yes' => array(
        'caption' => 'В списке',
        'type' => 'checkbox',
        'elements' => 'Да==1'
    ),
    'fltr_yes' => array(
        'caption' => 'Фильтр',
        'type' => 'checkbox',
        'elements' => 'Да==1'
    ),
    'fltr_type' => array(
        'caption' => 'Тип фильтра',
        'type' => 'dropdown',
        'elements' => '||Чекбокс==1||Список==2||Диапазон==3||Флажок==4||Мультиселект==5||Слайдер==6||Цвет==7||Паттерн==8||Одиночный чебокс==9'
    ),
    'fltr_name' => array(
        'caption' => 'Название фильтра',
        'type' => 'text'
    ),
    'fltr_many' => array(
        'caption' => 'Множественный',
        'type' => 'checkbox',
        'elements' => 'Да==1'
    ),
    'fltr_href' => array(
        'caption' => 'Ссылка',
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
