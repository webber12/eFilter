/**
 * iFilterIndexHelper
 *
 * Индексация iFilter
 *
 * @author        webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties &page_id=ID страницы;string;&prepareBeforeIndex=Метод до индексирования;string;&prepareAfterIndex=Метод после индексирования;string;&prepareBeforeSave=Метод до сохранения;string;&prepareAfterSave=Метод после сохранения;string;;
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 * @internal    @events OnDocFormRender,OnDocFormSave
 */

if($modx->event->name == 'OnDocFormRender') {

    $page_id = $params['page_id'] ?: evo()->getConfig('site_start');

    if($params['id'] != $page_id) return;

    $out = <<<HTML
    <script>
        jQuery(document).ready(function($){
            $("#actions>.btn-group").append('<a id="iFilterIndex" class="btn btn-primary" href="javascript:;"><i class="fa fa-eye"></i><span>Индекс iFilter</span></a>');
            $(document).on("click", "#iFilterIndex", function(e){
                e.preventDefault();
                $.get('/assets/snippets/eFilter/cron.php', function(data){
                    alert('Индексация завершена');
                });
            })
        })
    </script>
HTML;

    $modx->event->addOutput($out);
}

if($modx->event->name == 'OnDocFormSave') {
    $modx->db->update(
        ['editedon' => time() ],
        $modx->getFullTableName("site_content"),
        "id=" . $params['id']);
}
