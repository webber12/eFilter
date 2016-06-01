//<?php
/**
 * makeFilter
 * 
 * Делаем простой фильтр по заданному TV
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@modx_category Filters
 * @internal    @installset base, sample
 */
 
// Делаем простой фильтр по заданному id TV (либо нескольким однотипным через запятую)
// Пример вызова в категории (размещается в нужном месте внутри формы с action=get)
//
// [!makeFilter? &tvs=`58` &type=`checkbox`!] - вызывает список чекбоксов для TV с id=58
// или
// [!makeFilter? &tvs=`32,33,34,36,54` &type=`checkbox`!] - вызывает списки чекбоксов для TV с id по списку 32,33,34,36,54
// 


return require MODX_BASE_PATH . 'assets/snippets/eFilter/snippet.makeFilter.php';
