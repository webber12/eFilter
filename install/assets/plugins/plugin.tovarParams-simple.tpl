/**
 * tovarParams-simple
 *
 * plugin for convinient work with makeFilter
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @events OnDocFormRender
 * @internal    @properties
 * @internal    @installset base, sample
 * @internal    @modx_category Filters
 */
 
 /*использует общие параметры модуля eLists - не забудьте их подключить в модуле и плагине */
 /*
 предназначен для скрытия/показа только нужных tv из заданных категорий "параметры товара" в зависимости
 от настроек родительской категории по фильтрам и используемым параметрам товара
 имеет упрощенный конфиг по сравнению с tovarParams, т.к. нужен только для скрытия ненужных параметров.
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
    $product_template_array = explode(',', $product_templates_id);
    if (isset($content['template']) && in_array($content['template'], $product_template_array)) {
        //узнаем родителя, чтобы грузить конфиг
        if ($id == '0') {
            if (isset($_GET['pid'])) {
                $pid = $_GET['pid'];
            }
            if (isset($content['parent'])) {
                $pid = $content['parent'];
            }
            if (isset($_POST['pid'])) {
                $pid = $_POST['pid'];
            }
        } else {
            $pid = $modx->db->getValue("SELECT parent FROM " . $modx->getFullTableName('site_content') . " WHERE id={$id} LIMIT 0,1");
        }
        
        include_once(MODX_BASE_PATH . 'assets/snippets/eFilter/eFilter.class.php');
        $eFltr = new eFilter($modx, $params);
        $eFltr->docid = $pid;

        //получаем все возможные ТВ
        $sql = "SELECT `id`,`name`,`caption` FROM " . $modx->getFullTableName('site_tmplvars') . " WHERE `category` IN (" . $param_cat_id . ") ORDER BY `rank` ASC, `caption` ASC";
        $q = $modx->db->query($sql);
        while($row = $modx->db->getRow($q)){
            $tv_list[$row['id']]= $row['name'];
        }


        //разрешененные для данного типа товара параметры
        $tmp = $eFltr->getFilterParam ($eFltr->param_tv_name_simple);
        if (isset($tmp['fieldValue'])) {
            foreach ($tmp['fieldValue'] as $k=>$v) {
                $allowedParams[$v['param_id']] = '1';
            }
        }

        //строим массив запрещенных ТВ (на основе перечня всех ТВ и списка разрешенных)
        foreach ($tv_list as $k => $v) {
            if (!isset($allowedParams[$k])) {
                $disallowedParams[$k] = '1';
            }
        }
        
        //скрипт скрытия всех "запрещенных ТВ"
        //т.к. managermanager почти у всех и он уже подключил jquery, то обращаемся смело к нему
        if (!empty($disallowedParams)) {
            $output .= '<script type="text/javascript">$j(document).ready(function(){';
            foreach ($disallowedParams as $k => $v) {
                $output .= '$j("#tv' . $k . '").parents("tr").hide().next("tr").find("td[colspan=2]").parent("tr").hide();';
                //фикс чекбоксов
                $output .= '$j("input[name=\'tv' . $k . '[]\']").parents("tr").hide().next("tr").find("td[colspan=2]").parent("tr").hide();';
                //фикс радио
                $output .= '$j("input[name=\'tv' . $k . '\']").parents("tr").hide().next("tr").find("td[colspan=2]").parent("tr").hide();';
            }
            $output .= '})</script>';
            $modx->event->output($output);
        }
        
    }
}
