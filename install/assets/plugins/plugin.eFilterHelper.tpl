/**
 * eFilterHelper
 *
 * plugin for convinient work with eFilter
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @events OnDocFormRender,OnWebPageInit
 * @internal    @properties
 * @internal    @installset base, sample
 * @internal    @modx_category Filters
 */
 
/*использует общие параметры модуля eLists - не забудьте их подключить в модуле и плагине */
 /*
 предназначен для скрытия/показа только нужных tv из заданных категорий "параметры товара" в зависимости
 от настроек родительской категории по фильтрам и используемым параметрам товара
  а также для установки нужных направлений сортировки на событии onWebPageInit
 */

if(!defined('MODX_BASE_PATH')) die('What are you doing? Get out of here!');


$output = '';
//массив разрешеннных ТВ (id)
$allowedParams = array();
//массив запрещенных ТВ (id) - будем их скрывать
$disallowedParams = array();
//массив всех ТВ из категорий "параметры для товара" - $param_cat_id
$tv_list = array();

if($modx->event->name == 'OnDocFormRender') {
    global $content;
    global $tvsArray;
    $product_template_array = explode(',', $product_templates_id);
    if ((isset($content['template']) && in_array($content['template'], $product_template_array)) || !empty($tvsArray['tovarparams'])) {
        
        include_once(MODX_BASE_PATH . 'assets/snippets/eFilter/eFilter.class.php');
        $eFltr = new eFilter($modx, $params);

        //получаем все возможные ТВ
        $sql = "SELECT `id`,`name`,`caption` FROM " . $modx->getFullTableName('site_tmplvars') . " WHERE `category` IN (" . $param_cat_id . ") ORDER BY `rank` ASC, `caption` ASC";
        $q = $modx->db->query($sql);
        while($row = $modx->db->getRow($q)){
            $tv_list[$row['id']]= $row['name'];
        }
        
        //узнаем родителя, чтобы грузить конфиг tovarparams
        //приоритет у прямых родителей товара, по ним сначала и пройдемся
        $pid == '';
        $allowedParams = array();
        if (isset($_GET['pid'])) {
             $pid = $_GET['pid'];
        }
        if (isset($content['parent'])) {
            $pid = $content['parent'];
        }
        if (isset($_POST['pid'])) {
            $pid = $_POST['pid'];
        }
        if ($pid == '') {$pid = '1';}
        if (!empty($tvsArray['tovarparams']) && !empty($content['id'])) {
            $pid = $content['id'];
        }
        $eFltr->docid = $pid;
        //разрешенные для данного типа товара параметры
        $allowedTmp = $eFltr->getFilterParam ($eFltr->param_tv_name);
        
        //если параметров не обнаружено, пройдемся по первой тегованной категории
        //возможно, они есть там
        if (empty($allowedTmp) && isset($_GET['id']) && (int)$_GET['id'] != '0' && isset($tv_category_tag) && $tv_category_tag != '') {
            //определяем родителя по первой прикрепленной категории
            $category = $modx->db->getValue("SELECT value FROM" . $modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE tmplvarid={$tv_category_tag} AND contentid=" . (int)$_GET['id'] . " LIMIT 0,1");
            if ($category) {//категория есть
                $tmp = explode(',', $category);
                $pid = $tmp[0];
                if ($pid) {
                    $eFltr->docid = $pid;
                    //разрешенные для данного типа товара параметры
                    $allowedTmp = $eFltr->getFilterParam ($eFltr->param_tv_name);
                }
            }
        }
        
        //строим итоговый массив разрешенных для данного вида товара параметров
        if (isset($allowedTmp['fieldValue'])) {
            foreach ($allowedTmp['fieldValue'] as $k => $v) {
                $allowedParams[$v['param_id']] = '1';
            }
        }

        //строим массив запрещенных ТВ (на основе перечня всех ТВ и списка разрешенных)
        foreach ($tv_list as $k => $v){
            if (!isset($allowedParams[$k])) {
                $disallowedParams[$k] = '1';
            }
        }
        
        //скрипт скрытия всех "запрещенных ТВ"
        //т.к. managermanager почти у всех и он уже подключил jquery, то обращаемся смело к нему
        if (!empty($disallowedParams)) {
            $output .= '<script type="text/javascript">jQuery(document).ready(function(){';
            foreach ($disallowedParams as $k => $v) {
                $output .= 'jQuery(".sectionBody").find("#tv' . $k . '").parents("tr").addClass("hide_next");';
                //фикс чекбоксов
                $output .= 'jQuery(".sectionBody").find("input[name=\'tv' . $k . '[]\']").parents("tr").addClass("hide_next");';
                //фикс радио
                $output .= 'jQuery(".sectionBody").find("input[name=\'tv' . $k . '\']").parents("tr").addClass("hide_next");';
                //фикс templatesEdit3
                $output .= 'jQuery(".sectionBody").find("#tv' . $k . '").closest(".row.form-row").hide();';
            }
            $output .= '})</script>';
            $output .= '<style>tr.hide_next,tr.hide_next + tr{display:none;}</style>' . "\n";
        }
    }
    $isTovarParams = $modx->db->getValue("SELECT COUNT(*) FROM " . $modx->getFullTableName("site_tmplvar_templates") . " WHERE tmplvarid={$param_tv_id} AND templateid={$template}");
    if (!empty($isTovarParams)) {
        //есть tv tovarparams - будем его стилизовать
        $style = file_get_contents(MODX_BASE_PATH . 'assets/snippets/eFilter/html/tovarparams_style.tpl');
        $output .= $modx->parseText($style, array('param_tv_id' => $param_tv_id));
    }
    $modx->event->output($output);
}

if ($modx->event->name == 'OnWebPageInit') {
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
