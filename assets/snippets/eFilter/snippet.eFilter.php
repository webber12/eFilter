<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
$output = "";

include_once('eFilter.class.php');
$eFltr = new eFilter($modx, $params);

$eFltr->modx->regClientCSS('assets/snippets/eFilter/html/css/eFilter.css');
$eFltr->modx->regClientCSS('assets/snippets/eFilter/html/css/slider.css');
$eFltr->modx->regClientScript('assets/snippets/eFilter/html/js/jquery-ui.min.js');
$eFltr->modx->regClientScript('assets/snippets/eFilter/html/js/jquery.ui.touch-punch.min.js');
//вкл ajax
if (isset($params['ajax']) && $params['ajax'] == '1') {
    $eFltr->modx->regClientScript('<script>var eFiltrAjax = "1";</script>', array('plaintext' => true));
}
//автосабмит формы
$autoSubmit = isset($params['autoSubmit']) ? $params['autoSubmit'] : '1';
$eFltr->modx->regClientScript('<script>var eFiltrAutoSubmit = "' . $autoSubmit . '";</script>', array('plaintext' => true));
//режим аякс: 1 - полный, 2 - перегружается только форма, а список по кнопке submit без ajax
if (isset($params['ajaxMode']) && $params['ajaxMode'] != '') {
    $eFltr->modx->regClientScript('<script>var eFiltrAjaxMode = "' . $params['ajaxMode'] . '";</script>', array('plaintext' => true));
}
//изменять адрес url после запросов
if (isset($params['changeState']) && $params['changeState'] != '') {
    $eFltr->modx->regClientScript('<script>var eFiltrChangeState = "' . $params['changeState'] . '";</script>', array('plaintext' => true));
}
$eFltr->modx->regClientScript('assets/snippets/eFilter/html/js/eFilter.js');

//получаем значение параметров для категории товара в виде массива
//если у ресурса не задано - смотрим родителя, если у родителя нет- смотрим дедушку
$eFltr->filter_param = $eFltr->getFilterParam ($eFltr->param_tv_name);


//формируем основные массивы для описания наших фильтров
//на основе заданного в multiTV конфига
//$this->filter_tvs;
//$this->filter_names;
//$this->filter_cats;
//$this->filters;
//$this->list_tv_ids;

if (!empty($eFltr->filter_param['fieldValue'])) {//если не пусто, то формируем фильтры
    $eFltr->makeFilterArrays();
}


//получаем список id tv через запятую
//которые участвуют в фильтрации
//для последующих запросов
if (!empty($eFltr->filter_tvs)) {
    $eFltr->filter_tv_ids = implode(',', $eFltr->filter_tvs);
}


//получаем из конфигурации фильтра имена ТВ, которые используются в фильтрации
$eFltr->filter_tv_names = $eFltr->getTVNames ($eFltr->filter_tv_ids);


//получаем из конфигурации фильтра имена ТВ, которые используются в списке (для tvList DocLister)
if (!empty($eFltr->list_tv_ids)) {
    $eFltr->list_tv_names = $eFltr->getTVNames (implode(',', $eFltr->list_tv_ids));
    $eFltr->list_tv_captions = $eFltr->getTVNames (implode(',', $eFltr->list_tv_ids), 'caption');
    $eFltr->list_tv_elements = $eFltr->getTVNames (implode(',', $eFltr->list_tv_ids), 'elements');
}



//параметры DocLister по умолчанию
//он используется для поиска подходящих id ресурсов как без фильтров (категория, вложенность, опубликованность, удаленность и т.п.)
//так и с использованием фильтра
//на выходе получаем список id подходящих ресурсов через запятую
$DLparams = array('parents' => $eFltr->docid, 'depth' => isset($eFltr->params['depth']) ? $eFltr->params['depth'] : '3', 'addWhereList' => ((isset($eFltr->params['addWhereList']) && !empty($eFltr->params['addWhereList'])) ? $eFltr->params['addWhereList'] . ' AND ' : '') . 'c.template IN(' . $eFltr->params['product_templates_id'] . ')', 'makeUrl' => '0');
if(!empty($eFltr->params['showParent'])) {
    $DLparams['showParent'] = $eFltr->params['showParent'];
}
if(!empty($eFltr->params['showNoPublish'])) {
    $DLparams['showNoPublish'] = $eFltr->params['showNoPublish'];
}
$filter_ids = $modx->getPlaceholder("eFilter_filter_ids");
if ($filter_ids && $filter_ids != '') {
    $DLparams['addWhereList'] .= ' AND c.id IN (' . $filter_ids . ') ';
}
$DLparamsAPI = array('JSONformat' => 'new', 'api' => 'id', 'selectFields' => 'c.id');
$allProducts = $eFltr->getCategoryAllProducts($eFltr->docid, $eFltr->tv_category_tag);
if (empty($allProducts)) return;//если документов нет, то и делать ничего дальше не нужно

unset($DLparams['parents']);
unset($DLparams['depth']);
$DLparams['documents'] = $allProducts;
$DLparamsAll = array_merge($DLparams, $DLparamsAPI);

//это список всех id товаров данной категории, дальше будем вычленять ненужные :)
$_ = $eFltr->modx->runSnippet("DocLister", $DLparamsAll);
$eFltr->content_ids_full = $eFltr->getListFromJson($_);

//получаем $eFltr->content_ids
//это пойдет в плейсхолдер (список documents через запятую
//как все подходящие к данному фильтру товары
//для подстановки в вызов DocLister и вывода списка отфильтрованных товаров на сайте
$eFltr->makeAllContentIDs($DLparamsAll);

//начинаем формировать фильтр
//проходимся по каждому фильтру и берем список всех товаров с учетом всех фильтров кроме текущего
//формируем по итогам массив $eFltr->curr_filter_values
//в котором каждому id тв фильтра соответствует список документов, которые подходят для всего фильтра за 
//исключением текущего
$eFltr->makeCurrFilterValuesContentIDs($DLparamsAll);

//берем все доступные значения для параметров до фильтрации
$eFltr->filter_values_full = $eFltr->getFilterValues ($eFltr->content_ids_full, $eFltr->filter_tv_ids);

//берем доступные  значения для параметров после фильтрации
//и формируем вывод фильтра с учетом количества для каждого из значений фильтра
//количество считаем исходя из сформированного списка подходящих документов из массива $eFltr->curr_filter_values
$eFltr->filter_values = $eFltr->getFilterFutureValues ($eFltr->curr_filter_values, $eFltr->filter_tv_ids);

//выводим блок фильтров (доступных после фильтрации)
//итоговый фильтр на вывод
$output = $eFltr->renderFilterBlock ($eFltr->filter_cats, $eFltr->filter_values_full, $eFltr->filter_values, $eFltr->filters, $eFltr->cfg);

//устанавливаем плейсхолдеры
$eFltr->setPlaceholders (
    array(
        //список документов для вывода (подставляем в DocLister, это происходит автоматом в сниппете getFilteredItems)
        "eFilter_ids" => $eFltr->content_ids,
        
        //количество документов найденных при фильтрации
        //если искали и ничего не нашли (isset($_GET['f'])) - 0, если не искали - пусто ''
        "eFilter_ids_cnt" => $eFltr->content_ids_cnt,
        
        //товар-товара-товаров в зависимости от количества и пусто, если ничего не искали
        "eFilter_ids_cnt_ending" => $eFltr->content_ids_cnt_ending,
        
        //форма вывода фильтра - вставить плейсхолдер в нужное место шаблона
        "eFilter_form" => $output,
        
        //перечень tv для вывода в список товаров
        //нужно для обозначения в списке tvList вызова DocLister  в сниппете getFilteredItems
        "eFilter_tv_list" => $eFltr->list_tv_names,
        
        //перечень tv для вывода в список товаров
        //нужно для вывода названий параметра рядом с его значением
        "eFilter_tv_names" => $eFltr->list_tv_captions,
        
        //перечень elements tv для вывода в список товаров
        //нужно для определения откуда взято значение - из дерева или нет
        "eFilter_tv_elements" => $eFltr->list_tv_elements
    )
);
