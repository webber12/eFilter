/**
 * eFilterCatalog
 *
 * Установка нужных параметров для сортировки и display в сессию
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @events OnWebPageInit
 * @internal    @properties
 * @internal    @installset base, sample
 * @internal    @modx_category Filters
 */
 
$e = & $modx->event;
if($e->name == 'OnWebPageInit') {
    $docid = $modx->documentIdentifier;    
    if (isset($_POST['action'])) {
        $action = $modx->db->escape($_POST['action']);
        switch ($action) {
            case 'changesortBy':
                //ставим в сессию параметры сортировки и вывода
                $sortBy = ($_POST['sortBy'] && !empty($_POST['sortBy'])) ? $modx->db->escape($_POST['sortBy']) : '';
                $sortOrder = ($_POST['sortOrder'] && !empty($_POST['sortOrder'])) ? $modx->db->escape($_POST['sortOrder']) : '';
                $sortDisplay = ($_POST['sortDisplay'] && !empty($_POST['sortDisplay'])) ? $modx->db->escape($_POST['sortDisplay']) : '';
                if (!empty($sortBy)) {
                    $_SESSION['sortBy'] = $sortBy;
                }
                if (!empty($sortOrder)) {
                    $_SESSION['sortOrder'] = $sortOrder;
                }
                if (!empty($sortDisplay)) {
                    $_SESSION['sortDisplay'] = $sortDisplay;
                }
                $_SESSION['sortDocument'] = $docid;
                break;

            default:
                break;
            
        }
    }
    //срасываем установки сортировки при уходе на другую страницу
    if (isset($_SESSION['sortDocument']) && $_SESSION['sortDocument'] != $docid) {
        unset($_SESSION['sortDocument']);
        unset($_SESSION['sortOrder']);
        unset($_SESSION['sortBy']);
        unset($_SESSION['sortDisplay']);
    }
    
}
