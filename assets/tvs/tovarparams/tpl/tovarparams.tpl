<textarea id="tv[+id+]" name="tv[+id+]" cols="40" rows="15" onchange="documentDirty=true;" style="width:100%;display:none;">[+value+]</textarea>
    <link rel="stylesheet" href="media/style/common/font-awesome/css/font-awesome.min.css?v=4.7.0">
    <link href="https://fonts.googleapis.com/css?family=PT+Sans:400,400i,700&amp;subset=cyrillic" rel="stylesheet">
    <link rel="stylesheet" href="//cdn.webix.com/edge/webix.css" type="text/css">
    <style>
        body.webix_full_screen{overflow:auto !important;}
        .webix_view.webix_pager{margin-bottom:30px;}
        .webix_cell{-webkit-transition: all .3s,-moz-transition: all .3s,-o-transition: all .3s,transition: all .3s}
        .webix_cell:nth-child(odd){background-color:#f6f8f8;}
        .webix_cell:hover{background-color: rgba(93, 109, 202, 0.16);}
        .whiteBg{background:#ffffff !important;}
        .redBg{background: rgb(206, 85, 69) !important;}
    </style>
    <script src="//cdn.webix.com/edge/webix.js" type="text/javascript"></script> 
    <script src="//cdn.webix.com/site/i18n/ru.js" type="text/javascript" charset="utf-8"></script>
    
    <div id="testB" style="padding-bottom:0px;"></div>
    <div id="testA" style="padding-bottom:20px;"></div>
    <script>
    var type_options = {
        "1" : "Чекбокс",
        "2" : "Список",
        "3" : "Диапазон",
        "4" : "Флажок",
        "5" : "Мультиселект",
        "6" : "Слайдер",
        "7" : "Цвет",
        "8" : "Паттерн"
    };
    /*var tvs_options = [+tvs_list+];*/
    webix.ready(function(){
        webix.i18n.setLocale("ru-RU");
        webix.editors.$popup = {
                date:{
                    view:"popup",
                    body:{ 
                        view:"calendar", 
                        timepicker:true, 
                        timepickerHeight:50,
                        width: 320, 
                        height:300
                    }
                },
                text:{
                    view:"popup", 
                    body:{view:"textarea", width:350, height:150}
                }
            };
            btn = webix.ui({
                container:"testB",
                view:"toolbar", id:"mybar", elements:[
                        { view:"button", type:"iconButton", icon:"plus", label:"Добавить", width:140, click:"add_row"}, 
                        { view:"button", type:"iconButton", icon:"refresh",  label:"Обновить", width:140, click:"refresh"},
                        { view:"button", type:"iconButton", icon:"cut",  label:"Очистить таблицу", width:190, click:"remove", css:"redBg"}]
            });
                
            grid = webix.ui({
                container:"testA",
                view:"datatable",
                id:"t1",
                select:"row",
                resizeColumn:true,
				height:500,
                columns:[
                    { id:"id", header:"", css:"rank", width:50, sort:"int"},
                    { id:"param_id", header:"TV", editor:"select", options:"[+module_url+]action.php?action=get_tv_list&docid=[+docid+]&tvid=[+id+]", sort:"text", width:200},
                    { id:"fltr_yes", header:"Фильтр", template:"{common.checkbox()}", adjust:"header"},
                    { id:"fltr_type", header:"Тип", editor:"select", options:type_options, sort:"text"},
                    { id:"fltr_name", header:"Название", editor:"text", sort:"text", width:200},
                    { id:"fltr_many", header:"Множ", width:50, template:"{common.checkbox()}", adjust:"header"},
                    { id:"cat_name", header:"Категория" , editor:"text", sort:"text"},
                    { id:"list_yes", header:"Список", template:"{common.checkbox()}", adjust:"header"},
                    { id:"fltr_href", header:"Ссылка", width:65, template:"{common.checkbox()}", adjust:"header"}
                ],
                autowidth:true,
                /*autoheight:true,*/
                drag:true,
                editable:true,
                editaction: "dblclick",
                url: "[+module_url+]action.php?action=list&docid=[+docid+]&tvid=[+id+]",
                save: {
                    url:"[+module_url+]action.php?action=update&docid=[+docid+]&tvid=[+id+]",
                    on:{
                        onAfterSave:function(response, id, details){
                            getTvValue();
                        }
                    }
                },
                delete: "[+module_url+]action.php?action=delete&docid=[+docid+]&tvid=[+id+]"
            });
            
            grid.attachEvent("onAfterDrop", function(context, native_event){
                var items = [];
                grid.eachRow( 
                    function (row){ 
                        items.push(grid.getItem(row).id);
                    }
                )
                var new_order = items.join(',');
                if (new_order != '') {
                    save_order(new_order);
                }
            });
            
            webix.ui({
                view:"contextmenu",
                id:"cmenu",
                data:[{"value":"add","title":"Добавить"},{"value":"delete","title":"Удалить"}],
                template:"#title#",
                on:{
                    onItemClick:function(id){
                        switch (this.getItem(id).value) {
                            case 'add':
                                add_row();
                                break;
                            case 'delete':
                                del_row();
                                break;
                            default:
                                break;
                        }
                    }
                }
            });
            webix.$$("cmenu").attachTo(grid);

            
        })
        function getTvValue() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '[+module_url+]action.php?action=get_tv_value&docid=[+docid+]&tvid=[+id+]', false);
            xhr.send();
            if (xhr.status != 200) {
                  show_alert(xhr.status + ': ' + xhr.statusText, "alert-warning");
            } else {
                document.getElementById("tv[+id+]").innerHTML = xhr.responseText;
            }
        }
        function show_alert(text, level) {
            webix.alert(text, level, function(result){});
        }
        function refresh() {
            webix.storage.local.put("state", grid.getState());
            grid.clearAll();
            grid.load(grid.config.url, 'json', function(text, data, http_request){
                var state = webix.storage.local.get("state");
                if (state) {
                    grid.setState(state);
                }
            });
        }
        function save_order(new_order) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '[+module_url+]action.php?action=save_order&docid=[+docid+]&tvid=[+id+]&order=' + new_order, false);
            xhr.send();
            if (xhr.status != 200) {
                  //show_alert(xhr.status + ': ' + xhr.statusText, "alert-warning");
            } else {
                getTvValue();
                refresh();
            }
        }
        function add_row() {
            var selected = grid.getSelectedId();
            var num = selected||0;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '[+module_url+]action.php?action=add_row&module_id=[+module_id+]&docid=[+docid+]&tvid=[+id+]&num=' + num, false);
            xhr.send();
            if (xhr.status != 200) {
                  show_alert(xhr.status + ': ' + xhr.statusText, "alert-warning");
            } else {
                var ins = xhr.responseText;
                grid.add(ins, num);
                getTvValue();
                refresh();
            }
        }
        function del_row() {
            var selected = grid.getSelectedId();
            if (typeof(selected) !== "undefined") {
                webix.confirm("Вы уверены, что хотите удалить выбранную строку?", "confirm-warning", function(result){
                    if (result === true) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', '[+module_url+]action.php?action=del_row&module_id=[+module_id+]&docid=[+docid+]&tvid=[+id+]&num=' + selected, false);
                        xhr.send();
                        if (xhr.status != 200) {
                            show_alert(xhr.status + ': ' + xhr.statusText, "alert-warning");
                        } else {
                            grid.remove(selected);
                            getTvValue();
                            refresh();
                        }
                    }
                })
            } else {
                show_alert("Вы не выбрали строку для удаления", "alert-warning");
            }
        }
        function remove() {
            webix.confirm("Вы уверены, что хотите удалить всю информацию???", "confirm-warning", function(result){
                if (result === true) {
                    webix.confirm("Вы точно уверены, что надо удалить всю информацию про фильтры данной категории???", "confirm-warning", function(result){
                        if (result === true) {
                            webix.confirm("<br>Я сделал все, что мог.<br><br>Нажмете сейчас ОК - удаляю окончательно!!<br><br>", "confirm-error", function(result){
                                if (result === true) {
                                    var xhr = new XMLHttpRequest();
                                    xhr.open('GET', '[+module_url+]action.php?action=remove&module_id=[+module_id+]&docid=[+docid+]&tvid=[+id+]', false);
                                    xhr.send();
                                    if (xhr.status != 200) {
                                        show_alert(xhr.status + ': ' + xhr.statusText, "alert-warning");
                                    } else {
                                        getTvValue();
                                        refresh();
                                    }
                                }
                            })
                        }
                    })
                }
            })
        }
    </script>
