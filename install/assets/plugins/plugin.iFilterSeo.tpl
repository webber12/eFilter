/**
 * iFilterSeo
 *
 * iFilterSeo
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties
 * @internal    @modx_category Filters
 * @internal    @installset base, sample
 * @internal    @events OnPageNotFound,OnWebPageInit
 */

/* и создать зависимость к модулю eLists */

if ($modx->event->name == 'OnPageNotFound') {
    $uri = $_GET['q'] ?? '';
    if(strpos($uri, '/filter/') === false) return;
    $uri = explode('/filter/', $uri);
    $docid = $modx->getIdFromAlias($uri[0]);
    if(empty($docid) || empty($uri[1])) return;

    //проверяем, что эта страница существует и опубликована
    $id = $modx->db->getValue("select id from " . $modx->getFullTableName('site_content') . " where deleted=0 and published=1 and id={$docid}");
    if(empty($id)) return;

    //начинаем проверять вторую сторону
    if(!class_exists('\iFilter\Controller')) {
        include_once MODX_BASE_PATH . '/assets/snippets/eFilter/src/Controllers/Controller.php';
    }
    $controller = new \iFilter\Controller($modx, array_merge($params, [ 'docid' => $id ]));
    $seoFilter = $controller->processSeoFilter($uri[1]);

    if(!empty($seoFilter)) {
        $_GET['f'] = $seoFilter;
        $modx->sendForward($docid);
        exit();
    }
    return;
}

if ($modx->event->name == 'OnWebPageInit') {
    //обработка для фейковых страниц каталога, сделанных через sendForward
    if(empty($modx->getPlaceholder('forwardId'))) return;

    $uri = $_GET['q'] ?? '';
    if(strpos($uri, '/filter/') === false) return;
    $uri = explode('/filter/', $uri);


    $docid = $modx->getPlaceholder('forwardId');


    //начинаем проверять вторую сторону
    if(!class_exists('\iFilter\Controller')) {
        include_once MODX_BASE_PATH . '/assets/snippets/eFilter/src/Controllers/Controller.php';
    }
    $controller = new \iFilter\Controller($modx, array_merge($params, [ 'docid' => $docid ]));
    $seoFilter = $controller->processSeoFilter($uri[1]);


    if(!empty($seoFilter)) {
        $_GET['f'] = $seoFilter;
    }
    return;
}
