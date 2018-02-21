//<?php
/**
 * eFilter
 * 
 * Вывод фильтра
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@modx_category Filters
 * @internal    @installset base, sample
 */
 
 //импортировать общие параметры из модуля eLists
 
 //устанавливает нужные плейсхолдеры для вывода формы и результатов поиска
 //вызов [!eFilter!]
 //в результате формируется плейсхолдер [+eFilter_form+] для показа формы поиска
 //а также [+eFilter_ids+] - список подходящих id для вставки в DocLister и ряд других
 //
 // дополнительные параметры вызова
 // &removeDisabled=`1` - удалять варианты с нулевым результатом из списка возможных (по умолчанию - 0 - варианты в списке остаются с атрибутом disabled)
 // &ajax=`1` - режим ajax - подгрузка формы и результатов поиска после сабмита формы поиска без перезагрузки страницы (по умолчанию - отключен)
 //
 //это центральный сниппет для фильтрации
 //работает совместно с DocLister, multiTV и DocInfo - они должны быть установлены

require MODX_BASE_PATH . 'assets/snippets/eFilter/snippet.eFilter.php';
