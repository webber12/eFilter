//<?php
/**
 * makeFilters
 * 
 * Делаем простой фильтр по всем заданным для категории TV
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@modx_category Filters
 * @internal    @installset base, sample
 */
 
// Делаем простой фильтр по всем заданным для категории TV (в прикрепленном к категории TV filterparams (с конфигом multiTV filterparams)
// Пример вызова в категории
//
// [!makeFilters? &cfg=`[*filterparams*]`!] - в нужном месте внутри формы будет осуществлен вывод всех нужных параметров (чекбоксов, селектов и т.п.)
//


return require MODX_BASE_PATH . 'assets/snippets/makeFilter/snippet.makeFilters.php';
