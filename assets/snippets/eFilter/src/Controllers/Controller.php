<?php namespace iFilter;

class Controller
{
    protected $modx;
    protected $params;
    protected $tvTable;
    protected $tvValuesTable;
    protected $contentTable;

    //информация о применяемом фильтре из параметра tovarparams
    protected $filterConfig = [];

    //информация о тв, используемых в фильтре
    protected $tvInfo = [];

    //сюда собираем используемы при фильтрации id ресурсов
    protected $ids = [];

    //параметры, используемые при фильтрации их переданного fp или из массива $_GET['f']
    protected $fp = [];

    //Расширенная информация о тв, которые участвуют в фильтрации
    protected $fpInfo = [];

    //типы используемых фильтров
    protected $filterTypes = [];

    //путь к шаблонам
    protected $tplPath = '';

    protected $start;

    public function __construct($modx, $params = [])
    {
        $this->modx = $modx;
        $this->params = $params;
        $this->tvTable = $this->modx->getFullTableName('site_tmplvars');
        $this->contentTable = $this->modx->getFullTableName('site_content');
        $this->tvValuesTable = $this->modx->getFullTableName('site_tmplvar_contentvalues');

        $this->start = microtime(true);
    }

    public function process()
    {
        $action = $this->get('action');
        switch($action) {
            case 'index':
                //индексация
                return $this->makeIndex();
                break;
            default:
                //построение фильтра
                return $this->makeFilter();
                break;
        }
    }

    public function makeFilter()
    {
        $start = microtime(true);
        $this->setFilterTypes();
        $this->setTplPath();
        $this->loadFilterConfig();
        //dump($this->filterConfig);
        $this->loadTvInfo();
        //dd($this->tvInfo);
        $this->loadFP();
        //dump(microtime(true) - $this->start);
        $this->getContentIds();
        //dump(microtime(true) - $this->start);
        $filter = $this->processFilter();
        //dd(microtime(true) - $this->start);
        $html = $this->renderFilters($filter);

        $result = ['api' => $filter, 'html' => $html ];

        return $this->response($result);

    }

    protected function response($result)
    {
        $this->prepareResponse($result);
        $this->loadScripts();
        $api = $this->get('api', false);
        if(!empty($api)) {
            $apiFormat = $this->get('apiFormat', 'json');
            switch($apiFormat) {
                case 'array':
                    return $result;
                    break;
                case 'json':
                default:
                    return json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
            }
        } else {
            //если мы НЕ в режиме api - возвращаем пустую строку, все данные находятся в плейсхолдерах
            return '';
        }

    }

    protected function loadScripts()
    {
        if($this->get('api', false) !== false) return; //в режиме api ничего грузить не надо

        //вкл ajax
        if ($this->get('ajax', 0) == '1') {
            $this->modx->regClientScript('<script>var eFiltrAjax = "1";</script>', [ 'plaintext' => true ]);
        }
        $this->modx->regClientScript('<script>var eFiltrBlockActiveClass = "' . $this->get('activeBlockClass', 'active') . '";</script>', [ 'plaintext' => true ]);
        //автосабмит формы
        $autoSubmit = $this->get('autoSubmit', 1);
        $this->modx->regClientScript('<script>var eFiltrAutoSubmit = "' . $autoSubmit . '";</script>', [ 'plaintext' => true ]);
        //режим аякс: 1 - полный, 2 - перегружается только форма, а список по кнопке submit без ajax
        if ($this->get('ajaxMode', '') != '') {
            $this->modx->regClientScript('<script>var eFiltrAjaxMode = "' . $this->get('ajaxMode', '') . '";</script>', [ 'plaintext' => true ]);
        }
        if ($this->get('reloadForm', '1') != '1') {
            $this->modx->regClientScript('<script>var eFiltrReloadForm = "' . $this->get('reloadForm', '1') . '";</script>', [ 'plaintext' => true ]);
        }
        //изменять адрес url после запросов
        if ($this->get('changeState', '') != '') {
            $this->modx->regClientScript('<script>var eFiltrChangeState = "' . $this->get('changeState', '') . '";</script>', [ 'plaintext' => true ]);
        }
        if ($this->get('seoUrls', '') != '') {
            $this->modx->regClientScript('<script>var eFiltrSeoUrls = "1";</script>', [ 'plaintext' => true ]);
        }
        if ($this->get('choices', '') != '') {
            $this->modx->regClientScript('<script>var eFiltrChoices = "1";</script>', [ 'plaintext' => true ]);
            $this->modx->regClientScript("<script>var eFiltrChoicesOwner = '" . $this->getTpl('choicesOwner') . "';</script>", [ 'plaintext' => true ]);
            $this->modx->regClientScript("<script>var eFiltrChoicesRow = '" . $this->getTpl('choicesRow') . "';</script>", [ 'plaintext' => true ]);
        }
        if ($this->get('disableDefaultSlider', 0) == 1) {
            $this->modx->regClientScript("<script>var eFiltrDisableDefaultSlider = true;</script>", [ 'plaintext' => true ]);
        } else {
            $this->modx->regClientCSS('assets/snippets/eFilter/html/nouislider/nouislider.min.css');
            $this->modx->regClientScript('assets/snippets/eFilter/html/nouislider/nouislider.min.js');
        }
        if ($this->get('disableDefaultScript', false) === false) {
            $this->modx->regClientScript('assets/snippets/eFilter/html/js/eFilter.js');
        }
        $this->modx->regClientCSS('assets/snippets/eFilter/html/css/eFilter.css');
        return $this;
    }

    protected function prepareResponse($result)
    {
        //ставим плейсхолдеры
        //список найденных id ставим в плейсхолдер
        $ids = $this->ids['all'];
        if(!empty($this->fp)) {
            $ids = $this->ids['filtered'] ?? -1;
        }
        $plh = [
            //список документов для вывода (подставляем в DocLister, это происходит автоматом в сниппете getFilteredItems)
            "eFilter_ids" => implode(',', array_keys($ids)),

            //количество документов найденных при фильтрации
            //если искали и ничего не нашли (isset($_GET['f'])) - 0, если не искали - пусто ''
            "eFilter_ids_cnt" => $ids != -1 ? count($ids) : 0,

            //товар-товара-товаров в зависимости от количества и пусто, если ничего не искали
            "eFilter_ids_cnt_ending" => $this->plural(($ids != -1 ? count($ids) : 0), $this->get('endings', 'товар,товара,товаров')),

            //форма вывода фильтра - вставить плейсхолдер в нужное место шаблона
            "eFilter_form" => $result['html'],

            //доп. инфо по тв и значениям, используемым в фильтрации
            "eFilterSeoValues" => json_encode($this->fpInfo, JSON_UNESCAPED_UNICODE),

            //время отработки фильтрации
            "eFilterTime" => microtime(true) - $this->start,

        ];
        $this->setPlaceholders($plh);
        return $this;
    }

    public function renderFilters($filter)
    {
        $html = '';
        $categoryTpl = $this->getTpl('category');
        foreach($filter as $i => $category) {
            $category_filters = '';
            foreach($category['filters'] as $tvid => $filter) {
                $category_filters .= $this->renderFilter($filter, $tvid);
            }
            if(!empty($category_filters)) {
                $fields = [
                    'iteration' => $i,
                    'cat_name' => $category['category'],
                    'wrapper' => $category_filters,
                ];
                $html .= $this->parse($categoryTpl, $fields);
            }
        }
        if(!empty($html)) {
            $formTpl = $this->getTpl('form');
            $tmp = explode('?', $_SERVER['REQUEST_URI']);
            if (!empty($this->get('submitPage') && is_numeric($this->get('submitPage')))) {
                $form_url = $this->modx->makeUrl($this->get('submitPage'));
            } else {
                $form_url = !empty($tmp[0]) && $this->get('submitDocPage') !== false ? $tmp[0] : $this->modx->makeUrl($this->get('docid'));
            }
            if(!empty($this->get('seoUrls'))) {
                //для сео-урлов берем только часть урла до фильтра
                $form_url = explode('filter/', $form_url)[0];
            }
            $total = count($this->ids['all']);
            if(!empty($this->fp)) {
                $total = count($this->ids['filtered'] ?? []);
            }
            $fields = [
                'url' => $form_url,
                'form_method' => $this->get('formMethod', 'get'),
                'total' => $total,
                'total_endings' => $this->plural($total, $this->get('endings', 'товар,товара,товаров')),
                'wrapper' => $html,
                'btn_text' => $this->get('btnText', 'Найти'),
            ];
            $html = $this->parse($formTpl, $fields);
            if(!empty($html)) {
                $html .= $this->parse($this->getTpl('reset'), [ 'reset_url' => $form_url ]);
            }
        }
        return $html;
    }

    protected function renderFilter($filter, $tvid)
    {
        //Чекбокс==1||Список==2||Диапазон==3||Флажок==4||Мультиселект==5||Слайдер==6||Цвет==7||Паттерн==8||Одиночный чебокс==9
        $type = $this->filterTypes[ $this->filterConfig[$tvid]['fltr_type'] ?? 1 ] ?? 'checkboxes';
        $rowTpl = $this->getTpl($type.'Row');
        $ownerTpl = $this->getTpl($type.'Owner');
        $rows = $owner = '';
        $removeDisabled = $this->get('removeDisabled', false);
        switch($this->filterConfig[$tvid]['fltr_type']) {
            case 3: //диапазон
                $fields = [
                    'tv_id' => $tvid,
                    'name' => $filter['caption'],
                    'min' => $filter['values']['min'],
                    'max' => $filter['values']['max'],
                    'start' => $filter['values']['currMin'] ?: $filter['values']['min'],
                    'finish' => $filter['values']['currMax'] ?: $filter['values']['max'],
                ];
                if((float)$fields['start'] == (float)$fields['min'] && empty($this->get('setIntervalValues'))) {
                    $fields['start'] = '';
                }
                if((float)$fields['finish'] == (float)$fields['max'] && empty($this->get('setIntervalValues'))) {
                    $fields['finish'] = '';
                }
                $rows .= $this->parse($rowTpl, $fields);
                break;
            case 6: //слайдер
                $fields = [
                    'tv_id' => $tvid,
                    'name' => $filter['caption'],
                    'min' => $filter['values']['min'],
                    'max' => $filter['values']['max'],
                    'start' => $filter['values']['currMin'] ?: $filter['values']['min'],
                    'finish' => $filter['values']['currMax'] ?: $filter['values']['max'],
                ];
                $rows .= $this->parse($rowTpl, $fields);
                break;
            default:
                foreach($filter['values'] as $id => $row) {
                    if(!empty($removeDisabled) && empty($row['cnt'])) continue;//удаляем нулевые при соотв. параметре
                    $fields = [
                        'tv_id' => $tvid,
                        'value' => $id,
                        'name' => $row['title'],
                        'count' => $row['cnt'],
                        'disabled' => empty($row['cnt']) ? 'disabled' : '',
                        'selected' => !empty($row['checked']) ? 'checked' : '',
                        'seo_value' => $this->stripAlias($row['title']),
                    ];
                    if(in_array($this->filterConfig[$tvid]['fltr_type'], [ 9 ])) {
                        //для одиночного чекбокса нужна просто подпись "в наличии"
                        $fields['name'] = $this->get('singleCheckboxTitle', 'в наличии');
                    }
                    if(in_array($this->filterConfig[$tvid]['fltr_type'], [ 2, 5 ])) {
                        //для селекта и мультиселекта вместо checked нужен selected
                        $fields['selected'] = !empty($row['checked']) ? 'selected' : '';
                    }
                    if(in_array($this->filterConfig[$tvid]['fltr_type'], [ 7 ])) {
                        //для цвета доп. параметр
                        $fields['label_selected'] = !empty($row['checked']) ? 'active' : '';
                    }
                    if(in_array($this->filterConfig[$tvid]['fltr_type'], [ 8 ])) {
                        //для паттерна
                        $fields['label_selected'] = !empty($row['checked']) ? 'active' : '';
                        $fields['pattern_folder'] = $this->get('patternFolder', 'assets/images/pattern/');
                    }
                    $rows .= $this->parse($rowTpl, $fields);
                }
                break;
        }
        if(!empty($rows)) {
            $fields = [
                'tv_id' => $tvid,
                'active_block_class' => isset($this->fp[$tvid]) ? $this->get('activeBlockClass', 'active') : '',
                'tv_name' => $filter['name'],
                'name' => $filter['caption'],
                'wrapper' => $rows,
            ];
            $owner .= $this->parse($ownerTpl, $fields);
        }
        return $owner;
    }

    public function stripAlias($value)
    {
        return is_numeric($value) ? $value : $this->modx->stripAlias($value);
    }

    protected function parse($tpl, $data)
    {
        return $this->modx->tpl->parseChunk($tpl, $data);
    }

    protected function getFilterTypes()
    {
        return $this->filterTypes;
    }

    public function setFilterTypes()
    {
        $this->filterTypes = [
            1 => 'checkboxes',
            2 => 'select',
            3 => 'interval',
            4 => 'radios',
            5 => 'multiselect',
            6 => 'slider',
            7 => 'colors',
            8 => 'pattern',
            9 => 'checkbox',
        ];
        return $this;
    }

    protected function processFilter()
    {
        if(empty($this->filterConfig) || empty($this->ids['all'])) return [];

        $filter = [];

        $filteredIds = $this->ids['filtered'] ?? $this->ids['all'];
        foreach($this->filterConfig as $tvid => $row) {
            //dd(microtime(true) - $this->start);
            $indexes = $this->loadFilterIndex($tvid);
            $filter[$tvid] = [
                'name' => $this->tvInfo[$tvid]['name'],
                'caption' => $this->filterConfig[$tvid]['fltr_name'] ?: $this->tvInfo[$tvid]['caption'],
                'is_link' => !empty($this->filterConfig[$tvid]['fltr_href']),
            ];
            switch($this->filterConfig[$tvid]['fltr_type']) {
                //для диапазонов и слайдеров свой расчет исходя из мин. и макс значений
                case 3://диапазон
                case 6://слайдер
                    //количество тут считать не надо, тут нужны данные по текущим и общим краям диапазона
                    $filter[$tvid]['values']['currMin'] = $this->fp[$tvid]['values']['min'] ?? false;//выбранный в фильтре минимум
                    $filter[$tvid]['values']['currMax'] = $this->fp[$tvid]['values']['max'] ?? false;//выбранный в фильтре минимум
                    $minmax = $this->getMinMaxForInterval($tvid);
                    $filter[$tvid]['values']['min'] = $minmax['min'];
                    $filter[$tvid]['values']['max'] = $minmax['max'];
                    if(!empty($this->get('setIntervalValues'))) {
                        if($filter[$tvid]['values']['currMin'] === false) {
                            $filter[$tvid]['values']['currMin'] = $minmax['min'];
                        }
                        if($filter[$tvid]['values']['currMax'] === false) {
                            $filter[$tvid]['values']['currMax'] = $minmax['max'];
                        }
                    }
                    break;
                default:
                    //если мы в группе, которая уже участвует в фильтрации, то исключаем ее из массива filteredIds
                    if(!empty($this->fp[$tvid]['values'])) {
                        $fids = $this->getFilteredIdsWithoutTv($tvid);
                        //если фильтруем только по одной текущей группе, то берем стандартный список всех
                        if($fids === false) {
                            $fids = $this->ids['all'];
                        }
                    } else {
                        //иначе берем все отфильтрованные другими группами
                        $fids = $filteredIds;
                    }

                    foreach ($indexes as $value => $ids) {
                        if ($this->checkExists($ids)) {
                            $filter[$tvid]['values'][$value]['ids'] = [];
                            foreach ($ids as $id) {
                                if (isset($fids[$id])) {
                                    $filter[$tvid]['values'][$value]['ids'][] = $id;
                                }
                            }
                            $filter[$tvid]['values'][$value]['cnt'] = count($filter[$tvid]['values'][$value]['ids'] ?? []);
                            unset($filter[$tvid]['values'][$value]['ids']);
                        }
                    }
                    $filter[$tvid]['values'] = $this->prepareFilterValues($filter[$tvid]['values'] ?? [], $tvid, $filter[$tvid]['is_link']);
                    break;
            }
            //dump('process ' . $tvid . ' ' . (microtime(true) - $this->start));
        }
        //dd($filter);
        if(!empty($filter)) {
            $tmp = [];
            foreach($filter as $tvid => $row) {
                if(!empty($row['values'])) {
                    if(isset($row['values']['currMin']) && (!empty($row['values']['currMin']) || !empty($row['values']['currMax']))) {
                        $tmp[$tvid] = $row;
                    } else {
                        $values = [];
                        foreach($row['values'] as $value) {
                            if(!empty($value['checked'])) {
                                $values[] = $value;
                            }
                        }
                        if(!empty($values)) {
                            $tmp[$tvid] = $row;
                            $tmp[$tvid]['values'] = $values;
                        }
                    }
                }
            }
            $this->fpInfo = $tmp;
        }
        $filter = $this->makeFilterCategories($filter);

        return $filter;
    }

    protected function makeFilterCategories($filter)
    {
        $arr = [];
        $category = '';
        $i = 0;
        foreach($this->filterConfig as $tvid => $row) {

            if(empty($filter[$tvid])) continue;

            if($row['cat_name'] != $category) {
                $i++;
                $category = $row['cat_name'];
            }
            if(!isset($arr[$i])) {
                $arr[$i] = [
                    'category' => $category,
                    'filters' => [],
                ];
            }
            $arr[$i]['filters'][$tvid] = $filter[$tvid];
        }
        return $arr;
    }

    protected function prepareFilterValues($values, $tvid, $isLink)
    {
        $elements = $this->tvInfo[$tvid]['elements'] ?? [];
        $checked = $this->fp[$tvid]['values'] ?? [];

        //для селектора собираем элементы из дерева
        if($this->tvInfo[$tvid]['type'] == 'custom_tv:selector' && !empty($values)) {
            $ids = array_keys($values);
            $q = $this->modx->db->query("select id,pagetitle FROM " . $this->contentTable . " where id in(" . implode(',', $ids) . ")");
            while($row = $this->modx->db->getRow($q)) {
                $elements[$row['id']] = $row['pagetitle'];
            }
        }

        $arr = [];
        foreach($values as $value => $row) {
            $title = $elements[$value] ?? $value;
            if($isLink && is_numeric($value)) {
                $title = '<a href="' . $this->modx->makeUrl($value) . '">' . $title . '</a>';
            }
            $arr[$value] = [
                'id' => $value,
                'title' => $title,
                'cnt' => $row['cnt'],
                'checked' => in_array($value, $checked),
            ];
        }
        //теперь сортируем
        $arr = $this->sortFilterValues($arr, $tvid, $elements);
        return $arr;
    }

    protected function sortFilterValues($values, $tvid, $elements)
    {
        $nosortTvId = $this->get('nosortTvId', false);
        if(!empty($nosortTvId) && $nosortTvId != 'all') {
            $nosortTvId = array_map('trim', explode(',', $nosortTvId));
        }
        $sortByCountTvId = $this->get('sortByCountTvId', false);
        if(!empty($sortByCountTvId) && $sortByCountTvId != 'all') {
            $sortByCountTvId = array_map('trim', explode(',', $sortByCountTvId));
        }
        if(!empty($nosortTvId) && ($nosortTvId == 'all' || in_array($tvid, $nosortTvId)) && !empty($elements)) {
            //сортируем в порядке следования элементов
            $arr = [];
            foreach($elements as $id => $title) {
                if(isset($values[$id])) {
                    $arr[$id] = $values[$id];
                }
            }
        } else if(!empty($sortByCountTvId) && ($sortByCountTvId == 'all' || in_array($tvid, $sortByCountTvId))) {
            $arr = $values;
            uasort($arr, function ($a, $b) {
                if ((int)$a['cnt'] == (int)$b['cnt']) return 0;
                return (int)$a['cnt'] > (int)$b['cnt'] ? -1 : 1;
            });
        } else {
            $arr = $values;
            uasort($arr, function ($a, $b) {
                return is_numeric($a['title']) && is_numeric($b['title']) ? ($a['title'] < $b['title'] ? -1 : ($a > $b ? 1 : 0)) : strcasecmp(strtolower($a['title']), strtolower($b['title']));
            });
        }
        return $arr;
    }

    protected function getFilteredIdsWithoutTv($tvid)
    {
        $arr = [];
        //если фильтруем только по одной текущей группе, то ее не учитываем
        if(count($this->fp) == 1 && isset($this->fp[$tvid])) return false;

        foreach($this->fp as $id => $rows) {
            if($id == $tvid) continue;
            //собираем все "отфильтрованные id" кроме текущего тв
            $arr = empty($arr) ? array_keys($rows['filteredIdsByTv']) : $this->intersect($arr, array_keys($rows['filteredIdsByTv']));
        }
        return array_fill_keys($arr, 1);
    }

    protected function checkExists($ids) {
        foreach($ids as $id) {
            if(isset($this->ids['all'][$id])) return true;
        }
        return false;
    }

    protected function intersect($arr, $arr2)
    {
        $tmp = [];
        $arr = array_fill_keys($arr, 1);
        foreach($arr2 as $v) {
            if(isset($arr[$v])) {
                $tmp[] = $v;
            }
        }
        return $tmp;
    }

    protected function getContentIds()
    {
        //получаем сначала все id товаров, которые участвуют в фильтрации
        $ids = [];
        $plh = $this->modx->getPlaceholder('eFilter_search_ids');
        if(!empty($plh)) {
            //если список id установлен в плейсхолдер - просто забираем его
            $ids = array_map('trim', explode(',', $plh));
        } else {
            $docid = $this->get('parents', -1);
            $ids = $this->loadContentIdsFromCache($docid);
            if($ids === false) {
                //иначе собираем с родителей, мультикатегорий и привязанных категорий через tagSaver
                $params = [
                    'parents' => $docid,
                    'depth' => 5,
                    'showParent' => $this->get('showParent', 0),
                    'returnDLObject' => 1,
                    'makeUrl' => 0,
                    'selectFields' => 'c.id',
                ];
                $product_templates_id = $this->get('product_templates_id');
                if (!empty($product_templates_id)) {
                    $params['addWhereList'] = 'c.template in(' . $product_templates_id . ')';
                }
                $arr = $this->modx->runSnippet("DocLister", $params)->getDocs();
                if (!empty($arr)) {
                    $ids = array_keys($arr);
                }
                //привязанные к категории товары через MultiCategories - http://modx.im/blog/addons/5700.html
                $children = $this->getCategoryProductsMultiCategories($docid);
                if(!empty($children)) {
                    $ids = array_merge($ids, $children);
                }
                if(!empty($ids)) {
                    $ids = array_unique($ids);
                }
                $filename = $this->getIdsCacheFilename($docid);
                @file_put_contents($filename, json_encode($ids));
            }
        }
        $prepareBefore = $this->get('prepareBefore');
        if(is_callable($prepareBefore)) {
            $ids = call_user_func($prepareBefore, [
                'ids' => $ids,
                'filterConfig' => $this->filterConfig,
                'tvInfo' => $this->tvInfo,
                'fp' => $this->fp,
                'docid' => $this->get('docid', -1),
                'parents' => $this->get('parents', -1),
            ]);
        }
        $this->ids['all'] = is_array($ids) ? array_fill_keys($ids, 1) : [];
        $this->setFilteredContentIds();
        return;
    }

    protected function loadContentIdsFromCache($docid)
    {
        $filename = $this->getIdsCacheFilename($docid);
        if(!is_file($filename) || !is_readable($filename)) return false;
        if(date("U") - filemtime($filename) > (int)$this->get('cacheLifetime', 1 * 60 * 60)) {
            @unlink($filename);//кэш устарел
            return false;
        }
        $ids = file_get_contents($filename);
        return json_decode($ids, 1);
    }

    protected function setFilteredContentIds()
    {
        if(empty($this->fp) || empty($this->ids['all'])) return;

        $filteredIds = array_keys($this->ids['all']);
        foreach($this->fp as $k => $values) {
            $values = $values['values'] ?? [];
            if(isset($this->tvInfo[$k]) && isset($this->filterConfig[$k])) {
                $tmp = [];
                $filterIndex = $this->loadFilterIndex($k);
                if(!empty($filterIndex)) {
                    switch($this->filterConfig[$k]['fltr_type']) {
                        //для диапазонов и слайдеров свой расчет исходя из мин. и макс значений
                        case 3://диапазон
                        case 6://слайдер
                            //сначала сортируем индекс по возрастанию числового ключа
                            ksort($filterIndex, SORT_NUMERIC);
                            $currMin = $values['min'] ?? false;//выбранный в фильтре минимум
                            $currMax = $values['max'] ?? false;//выбранный в фильтре минимум

                            foreach($filterIndex as $value => $ids) {

                                if(!is_numeric($value)) continue;

                                if((empty($currMin) || $value >= (float)$currMin) && (empty($currMax) || $value <= (float)$currMax)) {
                                    //значение попадает в диапазон, при его наличии
                                    //берем только айди из нашего диапазона
                                    foreach($ids as $id) {
                                        if(isset($this->ids['all'][$id])) {
                                            $tmp[] = $id;
                                        }
                                    }
                                }
                            }
                            break;
                        //дефолтный расчет для списков, чекбоксов и т.п. исходя из массива выбранных значений
                        default:
                            foreach($values as $value) {
                                if(isset($filterIndex[$value])) {
                                    //внутри группы id складываем только те, что входят в общий диапазон
                                    //$tmp = array_merge($tmp, $filterIndex[$value]);
                                    foreach($filterIndex[$value] as $id) {
                                        if(isset($this->ids['all'][$id])) {
                                            $tmp[] = $id;
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
                $this->fp[$k]['filteredIdsByTv'] = array_fill_keys($tmp, 1);
                //затем находим пересечение со списком всех ids
                //$filteredIds = array_intersect($filteredIds, $tmp);
                $filteredIds = $this->intersect($filteredIds, $tmp);
            }
        }
        $this->ids['filtered'] = array_fill_keys($filteredIds, 1);
        return;
    }

    protected function getMinMaxForInterval($tvid)
    {
        $min = $max = false;
        $filterIndex = $this->loadFilterIndex($tvid);

        //сначала сортируем по возрастанию значения
        ksort($filterIndex, SORT_NUMERIC);
        foreach($filterIndex as $value => $ids) {
            if(!is_numeric($value)) continue;
            if($this->checkExists($ids)) {
                //если хотя бы один id подходит под наш исходный диапазон - берем данное значение и останавливаемся
                $min = $value;
                break;
            }
        }

        //теперь зайдем с другой стороны в поисках максимума и сортируем по убыванию
        krsort($filterIndex, SORT_NUMERIC);
        foreach($filterIndex as $value => $ids) {

            if(!is_numeric($value)) continue;

            if($this->checkExists($ids)) {
                //если хотя бы один id подходит под наш исходный диапазон - берем данное значение и останавливаемся
                $max = $value;
                break;
            }
        }
        return [ 'min' => (float)$min, 'max' => (float)$max ];
    }

    protected function loadFilterIndex($tvid)
    {
        $arr = [];
        $path = $this->getIndexFolderPath() . '/';
        $filename = $this->getIndexFilename($tvid);
        if(is_file($path . $filename) && is_readable($path . $filename)) {
            $fp = fopen($path . $filename, 'r');
            while (($buffer = fgets($fp)) !== false) {
                $tmp = json_decode($buffer, 1);
                //dump($tmp);
                if(!empty($tmp)) {
                    foreach($tmp as $value => $ids) {
                        $arr[$value] = $ids;
                    }
                    //$arr = $arr + $tmp;
                }
            }
        }
        return $arr;
    }

    protected function getCategoryProductsMultiCategories($id)
    {
        if (empty($this->get('useMultiCategories'))) return;

        $children = $categories = [];
        //добавляем дочерние категории
        $childIds = $this->modx->getChildIds($id, 5);
        if (!empty($childIds)) {
            $categories = array_values($childIds);
        }
        //и саму категорию
        $categories[] = $id;
        //берем все товары данных мультикатегорий
        $q = $this->modx->db->query("SELECT * FROM " . $this->modx->getFullTableName("site_content_categories") . " WHERE category IN (" . implode(',', $categories) . ")");
        while ($row = $this->modx->db->getRow($q)) {
            $children[] = $row['doc'];
        }
        return $children;
    }

    protected function loadFP()
    {
        $fp = $this->get('fp');
        if(!empty($fp)) {
            if(is_string($fp)) {
                $fp = json_decode($fp, 1);
            } else {
                $fp = (array)$fp;
            }
        } else {
            $fp = $_GET['f'] ?? [];
        }
        if(!empty($fp)) {
            foreach($fp as $tvid => $values) {
                $values = array_diff($values, ['']);
                if(!empty($values)) {
                    $this->fp[$tvid]['values'] = $values;
                }
            }
        }
        return $this;
    }

    protected function loadTvInfo()
    {
        if(empty($this->filterConfig)) return [];

        $ids = array_column($this->filterConfig, 'param_id');
        $q = $this->modx->db->query('select * from ' . $this->tvTable . ' where id in(' . implode(',', $ids) . ')');
        while($row = $this->modx->db->getRow($q)) {
            $element = $row['elements'];
            $elements = [];
            if(!empty($element)) {
                if (stristr($element, "@EVAL")) {
                    $element = trim(substr($element, 6));
                    $element = str_replace("\$modx->", "\$this->modx->", $element);
                    $element = eval($element);
                }
                if (!empty($element)) {
                    $tmp = explode("||", $element);
                    foreach ($tmp as $v) {
                        $tmp2 = explode("==", $v);
                        $key = isset($tmp2[1]) && $tmp2[1] != '' ? $tmp2[1] : $tmp2[0];
                        $value = $tmp2[0];
                        if ($key != '') {
                            $elements[$key] = $value;
                        }
                    }
                }
            }
            $row['elements'] = $elements;
            $this->tvInfo[ $row['id'] ] = $row;
        }
        return $this;
    }

    protected function loadFilterConfig($type = 'filter')
    {
        $config = $this->get('filterConfig');

        if(empty($config)) {
            $docid = $this->get('docid');
            $param_tv_id = $this->get('param_tv_id');
            if (empty($docid) || empty($param_tv_id)) return [];

            $config = [];
            $parents = array_values(evo()->getParentIds($docid));
            array_unshift($parents, $docid);
            $q = $this->modx->db->query("select `contentid`,`value` from " . $this->tvValuesTable . " 
            where tmplvarid={$param_tv_id} and contentid in(" . implode(',', $parents) . ") 
            order by FIND_IN_SET (`contentid`, '" . implode(',', $parents) . "')");
            while ($row = $this->modx->db->getRow($q)) {
                if (!empty($row['value'])) {
                    $tmp = json_decode($row['value'], 1);
                    if (!empty($tmp['fieldValue'])) {
                        $config = $this->prepareFilterConfig($tmp['fieldValue']);
                        break;
                    }
                }
            }
        } else {
            $config = $this->prepareFilterConfig($config);
        }
        if(!empty($config)) {
            foreach($config as $row) {
                $this->filterConfig[ $row['param_id'] ] = $row;
            }
        }
        return $this->filterConfig;
    }

    protected function prepareFilterConfig($config, $type = 'filter')
    {
        switch ($type) {
            case 'all':
                //все
                break;
            case 'list':
                //с птичкой "список"
                $config = array_filter($config, function ($v) {
                    return $v['list_yes'] == 1;
                });
                break;
            case 'filter':
            default:
                //с птичкой "фильтр"
                $config = array_filter($config, function ($v) {
                    return $v['fltr_yes'] == 1;
                });
                break;
        }
        return $config;
    }

    protected function makeIndex()
    {
        if(!$this->checkIndex()) return false;

        //очищаем папку с индексами
        $this->clearIndexFolder();
        //индексируем тв из категорий param_cat_id и param_cat_id_common
        $ids = [];
        $param_cat_id = $this->get('param_cat_id', null, true);
        if(!empty($param_cat_id)) {
            $ids = array_merge($ids, array_map('trim', explode(',', $param_cat_id)));
        }
        $param_cat_id_common = $this->get('param_cat_id_common', null, true);
        if(!empty($param_cat_id_common)) {
            $ids = array_merge($ids, array_map('trim', explode(',', $param_cat_id_common)));
        }
        $i = 0;//посчитаем, сколько тв проиндексировано
        if(!empty($ids)) {
            $q = $this->modx->db->query("select `id` from " . $this->tvTable . ' where `category` in(' . implode(',', $ids) . ')');
            while($row = $this->modx->db->getRow($q)) {
                $this->makeTVIndex($row['id']);
                $i++;
            }
        }
        evo()->setPlaceholder('iFilterIndexed', $i);
        return $i;
    }

    protected function checkIndex()
    {
        $flag = true;
        if(!empty($this->get('ttl'))) {
            $time = time() - (int)$this->get('ttl');
            $sql = "select count(id) from " . $this->contentTable . " where editedon >= " . $time;
            $flag = $this->modx->db->getValue($sql);
        }
        return $flag;
    }

    protected function makeTVIndex($tvid)
    {
        $arr = [];
        $indexesPath = $this->getIndexFolderPath() . '/';
        $indexFilename = $this->getIndexFilename($tvid);
        if(is_file($indexesPath . $indexFilename)) {
            //удаляем индексный файл, если он существует
            unlink($indexesPath . $indexFilename);
        }
        $prepare = $this->get('tv_index_prepare', null, false);

        //собираем значения данного тв
        $q = $this->modx->db->query("select `contentid`,`value` from " . $this->tvValuesTable . " where tmplvarid=" . $tvid);
        while($row = $this->modx->db->getRow($q)) {
            $values = [];
            if(!is_string($row['value'])) continue;
            if(strpos($row['value'], '||') !== false) {
                $values = explode('||', $row['value']);
            } else {
                $values[] = $row['value'];
            }
            foreach($values as $value) {
                if(is_callable($prepare)) {
                    $value = call_user_func($prepare, $value, $tvid, $row['contentid']);
                }

                if(empty($value)) continue;

                if(is_string($value)) {
                    if (!isset($arr[$value])) {
                        $arr[$value] = [];
                    }
                    $arr[$value][] = (int)$row['contentid'];
                } else if (is_array($value)) {
                    foreach($value as $v) {
                        if (!isset($arr[$v])) {
                            $arr[$v] = [];
                        }
                        $arr[$v][] = (int)$row['contentid'];
                    }
                }
            }
        }
        if(!empty($arr)) {
            foreach($arr as $k => $values) {
                $str = json_encode([ $k => $values ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
                @file_put_contents($indexesPath . $indexFilename, $str, FILE_APPEND);
            }
        }
        return true;
    }

    protected function getIndexFilename($tvid)
    {
        return 'tv_' . $tvid . '.dat';
    }

    protected function clearIndexFolder()
    {
        $path = $this->getIndexFolderPath();
        foreach(glob($path . '/tv_*.dat') as $filename) {
            //удаляем предыдущие индексы
            @unlink($filename);
        }
        $path = $this->getIdsCacheFolderPath();
        foreach(glob($path . '/id_*.dat') as $filename) {
            //удаляем предыдущие кэши списков id категорий
            @unlink($filename);
        }
        return $this;
    }

    protected function getIndexFolderPath()
    {
        $path = __DIR__ . '/../../indexes';
        if(!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        return $path;
    }

    protected function getIdsCacheFolderPath()
    {
        $path = __DIR__ . '/../../idsCache';
        if(!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        return $path;
    }

    protected function getIdsCacheFilename($docid)
    {
        return $this->getIdsCacheFolderPath() . '/id_' . $docid . '.dat';
    }

    protected function getTpl($name)
    {
        $tpl = '';
        $path = $this->tplPath . $name  . '.tpl';
        if(is_file($path) && is_readable($path)) {
            $tpl = @file_get_contents($path);
        }
        return $tpl;
    }

    protected function setTplPath()
    {
        $cfg = $this->get('cfg', 'default');
        $this->tplPath = __DIR__ . '/../../tpl/' . $cfg . '/';
        return $this;
    }

    protected function get($key, $default = null, $callback = false)
    {
        $out = $this->params[$key] ?? $default;
        if($callback && !empty($out) && is_callable($out)) {
            return call_user_func($out);
        }
        return $out;
    }

    protected function plural($n, $forms = 'товар,товара,товаров') {
        $forms = array_map('trim', explode(',', $forms));
        $n = $n ?: 0;
        return $n%10==1&&$n%100!=11?$forms[0]:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$forms[1]:$forms[2]);
    }

    protected function setPlaceholders($array = [])
    {
        if (!empty($array)) {
            foreach ($array as $k => $v) {
                $this->modx->setPlaceholder($k, $v);
            }
        }
    }

    public function processSeoFilter($uri)
    {
        $uri = trim($uri, '/');
        $tmp = explode('/', $uri);
        $parts = [];
        $fp = [];
        foreach($tmp as $row) {
            $tmp2 = explode('-is-', $row, 2);
            if(count($tmp2) == 2) {
                $tvname = $tmp2[0];
                $values = [];
                if(strpos($tmp2[1], 'from-') !== false || strpos($tmp2[1], 'to-') !== false) {
                    //это слайдер
                    if(strpos($tmp2[1], 'from-') !== false && strpos($tmp2[1], '-to-') !== false) {
                        //есть и минимум и максимум
                        $values['max'] = explode('-to-', $tmp2[1])[1];
                        $values['min'] = explode('from-', explode('-to-', $tmp2[1])[0])[1];
                    } else if(strpos($tmp2[1], 'to-') !== false && strpos($tmp2[1], '-to-') === false) {
                        //есть только максимальное значение
                        $values['max'] = explode('to-', $tmp2[1])[1];
                    } else if(strpos($tmp2[1], 'from-') !== false && strpos($tmp2[1], '-from-') === false) {
                        //есть только минимальное значение
                        $values['min'] = explode('from-', $tmp2[1])[1];
                    } else {

                    }
                } else {
                    $values = explode('-or-', $tmp2[1]);
                }
                $parts[$tvname] = $values;
            }
        }
        $this->loadFilterConfig();
        $this->loadTvInfo();
        if(empty($this->filterConfig) || empty($this->tvInfo)) return false;
        $filter = $exists = [];
        foreach($this->filterConfig as $tvid => $row) {
            //нужно чтобы тв шли в том же порядке, что и в конфиге во избежание дублей
            $exists[] = $this->tvInfo[$tvid]['name'];
            $filter[ $this->tvInfo[$tvid]['name'] ] = $tvid;
        }
        $tvNamesUri = array_keys($parts);
        $tvNamesConfig = $exists;
        if(count($tvNamesConfig) < count($tvNamesUri)) return false;
        $k = 0;
        foreach($tvNamesUri as $i => $tvname) {
            $key = array_search($tvname, $tvNamesConfig);
            if($key === false || $key < $k) {
                return false;
            }
            $k = $key;
        }
        foreach($parts as $tvname => $values) {
            $tvid = $filter[$tvname];
            if(isset($values['min']) || isset($values['max'])) {
                //это диапазон, тут проверять наличие значений не нужно
                $fp[$tvid] = $values;
            } else {
                //если есть "возможные значения" - сверяемся с ними
                $realValues = [];
                if($this->tvInfo[$tvid]['type'] == 'custom_tv:selector') {
                    //для селектора грузим его элементы
                    $this->tvInfo[$tvid]['elements'] = $this->loadSelectorElements($tvid);
                }
                if(!empty($this->tvInfo[$tvid]['elements'])) {
                    $keys = array_keys($this->tvInfo[$tvid]['elements']);
                    $elements = array_map([ $this, 'stripAlias' ], array_values($this->tvInfo[$tvid]['elements']));
                    foreach($values as $value) {
                        $key = array_search($value, $elements);
                        if($key === false || !isset($keys[$key])) return false; //такого значения нет в списке возможных
                        $realValues[] = $keys[$key];
                    }
                    if(empty($realValues)) return false;
                    $fp[$tvid] = $realValues;
                } else {
                    //если возможных значений нет - берем из индекса
                    $indexes = $this->loadFilterIndex($tvid);
                    $keys = [];
                    foreach(array_keys($indexes) as $realValue) {
                        $alias = $this->stripAlias($realValue);
                        $keys[$alias] = $realValue;
                    }
                    foreach($values as $value) {
                        if(!isset($keys[$value])) return false;
                        $realValues[] = $keys[$value];
                    }
                    if(empty($realValues)) return false;
                    $fp[$tvid] = $realValues;
                }
            }
        }
        if(!empty($fp)) {
            $seoData = [];
            foreach($fp as $tvid => $values) {
                $seoData[ $this->tvInfo[$tvid]['name'] ]['tv'] = $this->tvInfo[$tvid];
                if(!empty($parts[ $this->tvInfo[$tvid]['name'] ]['min']) || !empty($parts[ $this->tvInfo[$tvid]['name'] ]['max'])) {
                    $seoData[ $this->tvInfo[$tvid]['name'] ]['values'] = $parts[ $this->tvInfo[$tvid]['name'] ];
                } else {
                    $v = [];
                    foreach ($values as $value) {
                        $v[] = $this->tvInfo[$tvid]['elements'][$value] ?? $value;
                    }
                    $seoData[ $this->tvInfo[$tvid]['name'] ]['values'] = $v;
                }
            }
            evo()->setPlaceholder('iFilterSeoData', json_encode($seoData, JSON_UNESCAPED_UNICODE));
            return $fp;
        }
        return false;
    }

    protected function loadSelectorElements($tvid)
    {
        $elements = [];
        $indexes = $this->loadFilterIndex($tvid);
        if(!empty($indexes)) {
            $q = $this->modx->db->query('select id,pagetitle from ' . $this->contentTable . ' 
                where id in(' . implode(',', array_keys($indexes)) . ')'
            );
            while($row = $this->modx->db->getRow($q)) {
                $elements[$row['id']] = $row['pagetitle'];
            }
        }
        return $elements;
    }
}
