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
/****************
//немного параметров в вызов
//количество видимых строк (чекбоксов в блоке, для списка чекбоксов предварительно поставить высоту и overflow:hidden)
//добавляет к блоку строку из параметра &visible_row_text, на которую вешается скрипт раскрытия/скрытия блока (пишется отдельно) и класс hidden_rows к самому блоку
//  &visible_row=`3`;
//строка, показывается при наличии скрытых строк в фильтре
//  &visible_row_text=`<div class="filter_more"><a href="#">Ещё...</a></div>`
//количество видимых (остальные скрыты - присваиваем блоку класс hidden)
//добавляет после всех блоков строку из параметра &visible_block_text, на которую можно повесить скрипт скрытия/показа скрываемых блоков
//  &visible_block=`4`;
//строка показывается после всех блоков фильтра (при наличии скрытых)
//  &visible_block_text=`<div class="filter_show_all"><span data-target-class="filter_block_inner.hidden">Показать все критерии</span></div>`
*********************/


return require MODX_BASE_PATH . 'assets/snippets/eFilter/snippet.makeFilters.php';
