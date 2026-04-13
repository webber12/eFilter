;
!function(wnd, $, undefined){
    var autoSubmit = wnd.eFiltrAutoSubmit||1;
    var useAjax = wnd.eFiltrAjax;
    var ajaxMode = wnd.eFiltrAjaxMode||1;
    var reloadForm = wnd.eFiltrReloadForm||1;
    var doChangeState = wnd.eFiltrChangeState||1;
    var blockActiveClass = wnd.eFiltrBlockActiveClass||'active';
    var eFiltrChoicesRow = wnd.eFiltrChoicesRow || '';
    var eFiltrChoicesOwner = wnd.eFiltrChoicesOwner || '';
    var eFilter = function(options) {
        this.Init(options);
    }
    eFilter.prototype = {
        constructor : eFilter,
        defaults : {
            block : "#eFiltr",
            form : "form#eFiltr",
            form_btn : ".eFiltr_btn",
            form_selector : "form#eFiltr input:not(.stop_event), form#eFiltr select:not(.stop_event)",
            result_list : "#eFiltr_results",
            loader : "#eFiltr_results_wrapper .eFiltr_loader"
        },
        params : {},
        Init : function(options) {
            this.params = $.extend({}, this.defaults, options);
            this.params = $.extend(this.params,
                {
                    block_obj : $(this.params.block),
                    form_obj : $(this.params.form),
                    form_obj_btn : $(this.params.form_btn),
                    form_selector_obj : $(this.params.form_selector),
                    result_list_obj : $(this.params.result_list),
                    loader_obj : $(this.params.loader)
                }
            );
            this.checkActions();
        },
        checkActions : function() {
            this.bindForm();
            this.checkForm();
            this.bindFormBtn();
            this.checkSort();
            this.checkPagination();
            this.initSliders();
            this.initChoices();
            this.bindRemoveChoice();
        },
        checkPagination : function() {
            var self = this;
            var a = $("#eFiltr_results .paginate a").length > 0 && $("#eFiltr_results .paginate a.ef_page").length > 0 ? ".paginate a.ef_page" : ".paginate a";
            $(document).on("click", a, function(e){
                if (typeof useAjax !== 'undefined') {
                    e.preventDefault();
                    var _form = '';
                    var data2 = '';
                    var action = $(this).attr("href");
                    self.makeAjax(action, data2, _form, "POST");
                    self.scrollTop();
                }
            })
        },
        checkForm : function() {
            var self = this;
            $(document).on("change", this.params.form_selector, function(e) {
                if (typeof autoSubmit !== 'undefined' && autoSubmit == '1' && !$(this).hasClass("eFiltr_submitted")) {
                    //self.submitForm();
                    $(document).find(self.params.form).submit();
                }
            })
        },
        checkSort : function() {
            
        },
        bindForm : function() {
            var self = this;
            $(document).on("submit", this.params.form, function(e) {
                if (typeof useAjax !== 'undefined' && !$(this).hasClass("eFiltr_submitted")) {
                    e.preventDefault();
                    $(document).trigger("before-efilter-form-serialize", [ $(this) ]);
                    var _form = $(this);
                    var data2 = _form.serialize()/* + '&no_ajax_for_star_rating=1'*/;
                    var action = _form.attr("action");
                    self.makeAjax(action, data2, _form, "GET", "all");
                }
            })
        },
        bindFormBtn : function() {
            var self = this;
            $(document).on("click", this.params.form_btn, function(e) {
                if (ajaxMode == '2') {
                    e.preventDefault();
                    $(document).find(self.params.form).addClass("eFiltr_submitted").submit();
                }
            })
        },
        makeAjax : function(action, data2, _form, type, updateAll) {
            let self = this;
            $.ajax({
                url: action,                                   
                data: data2,
                type: type,   
                beforeSend:function() {
                    self.prepareBeforeSend(_form, updateAll);
                    $(document).trigger("before-efilter-send", [ _form, updateAll ]);
                },                   
                success: function(msg) {
                    $(document).trigger("before-efilter-update", [ msg, _form, updateAll ]);
                    self.updateAfterSuccess(msg, _form, updateAll);
                    $(document).trigger("after-efilter-update", [ msg, _form, updateAll ]);
                    var state = action + (data2 != '' ? '?' + data2 : '');
                    self.changeState(state);
                    $(document).trigger("after-efilter-change-state", [ state ]);
                    self.initSliders();
                    self.initChoices();
                }
            })
        },
        blurBlocks : function() {
            this.params.form_obj.css({'opacity' : '0.5'});
            if (ajaxMode == '1') {
                this.params.result_list_obj.css({'opacity' : '0.5'});
            }
        },
        unblurBlocks : function() {
            this.params.form_obj.css({'opacity' : '1'});
            if (ajaxMode == '1') {
                this.params.result_list_obj.css({'opacity' : '1'});
            }
        },
        showLoader : function() {
            if (ajaxMode == '1') {
                this.params.loader_obj.show();
            }
        },
        hideLoader : function() {
            if (ajaxMode == '1') {
                this.params.loader_obj.hide();
            }
        },
        insertResult : function(msg, selector) {
            $(selector).html($(msg).find(selector).html());
        },
        updateAfterSuccess : function(msg, _form, updateAll) {
            if (typeof afterFilterSend == 'function') {
                afterFilterSend(msg);
            }
            this.hideLoader();
            if (ajaxMode == '1') {
                this.insertResult(msg, this.params.result_list);
            }
            if (typeof updateAll !== 'undefined' && reloadForm == '1') {
                this.insertResult(msg, this.params.form);
            }
            this.unblurBlocks();
            if (typeof(afterFilterComplete) == 'function') {
                afterFilterComplete(_form);
            }
        },
        prepareBeforeSend : function(_form, updateAll) {
            if (typeof beforeFilterSend == 'function') {
                beforeFilterSend(_form);
            }
            this.blurBlocks();
            this.showLoader();
        },
        scrollTop : function() {
            var t = this.params.result_list_obj.offset().top >= 100 ? this.params.result_list_obj.offset().top - 100 : 0;
            $('html,body').animate({ scrollTop: t }, 300);
        },
        changeState : function(state) {
            if (ajaxMode == '1' && doChangeState != '0') {
                if(typeof eFiltrSeoUrls != "undefined") {
                    state = this.seoState(state);
                }
                history.pushState('', '', state);
                if ($("#changesortBy").length > 0) {
                    $("#changesortBy").attr("action", state);
                }
            }
        },
        seoState: function(state) {
            console.log('state ' + state);
            let self = this;
            let url = '';
            let v;
            this.params.block_obj.find(".fltr_block." + blockActiveClass).each(function(){
                switch(true) {
                    case self.radioBlock($(this)):
                        url += $(this).data('tvName') + '-is-' + $(this).find(":checked").data("seoValue") + '/';
                        break;
                    case self.checkboxesBlock($(this)):
                        v = [];
                        $(this).find(":checked").each(function(){
                            v.push($(this).data("seoValue"));
                        })
                        if(v.length > 0) {
                            url += $(this).data('tvName') + '-is-' + v.join('-or-') + '/';
                        }
                        break;
                    case self.selectBlock($(this)):
                        v = [];
                        $(this).find("option:selected").each(function(){
                            v.push($(this).data("seoValue"));
                        })
                        if(v.length > 0) {
                            url += $(this).data('tvName') + '-is-' + v.join('-or-') + '/';
                        }
                        break;
                    case self.sliderBlock($(this)):
                        v = '';
                        let min = $(this).find("[data-slider]").data("min");
                        let max = $(this).find("[data-slider]").data("max");
                        let start = $(this).find("[data-slider]").data("start");
                        let finish = $(this).find("[data-slider]").data("finish");
                        if(parseFloat(start) != parseFloat(min)) {
                            v += '-from-' + start;
                        }
                        if(parseFloat(finish) != parseFloat(max)) {
                            v += '-to-' + finish;
                        }
                        if(v != '') {
                            url += $(this).data('tvName') + '-is' + v + '/';
                        }
                        break;
                    default:
                        break;
                }
            })
            let action = this.params.block_obj.attr("action").split('filter/');
            return url == '' ? action[0] : action[0] + 'filter/' + url;
        },
        checkboxesBlock: function(block){
            return block.find("input[type='checkbox']:checked").length > 0;
        },
        radioBlock: function(block){
            return block.find("input[type='radio']:checked").length > 0;
        },
        selectBlock: function(block){
            return block.find("option:selected").length > 0;
        },
        sliderBlock: function(block){
            return block.find("[data-slider]").length > 0;
        },
        bindRemoveChoice: function(){
            if(typeof eFiltrChoices == "undefined") return;//не нужно показывать выбранные значения

            let self = this;

            $("[data-filter-choices]").on("click", "[data-choices-row]", function(){
                let tvName = $(this).closest("[data-tv-name]").data("tvName");
                let value_id = $(this).data("choicesRow");
                let fltrBlock = self.params.block_obj.find(".fltr_block[data-tv-name='" + tvName + "']");
                switch(true) {
                    case self.radioBlock(fltrBlock):
                    case self.checkboxesBlock(fltrBlock):
                        fltrBlock.find("[data-value-block='" + value_id + "'] :checked").prop("checked", "").trigger("change");
                        if(fltrBlock.find(":checked").length < 1) {
                            self.params.block_obj.trigger("submit");
                        }
                        break;
                    case self.selectBlock(fltrBlock):
                        fltrBlock.find("[data-value-block='" + value_id + "']").prop("selected", "");
                        fltrBlock.find("select").trigger("change");
                        break;
                    case self.sliderBlock(fltrBlock):
                        fltrBlock.find("[data-start-input]").val(fltrBlock.find("[data-slider]").data("min"));
                        fltrBlock.find("[data-finish-input]").val(fltrBlock.find("[data-slider]").data("max"));
                        self.params.block_obj.trigger("submit");
                        break;
                    default:
                        break;
                }

            })
        },
        initChoices: function(){
            if(typeof eFiltrChoices == "undefined") return;//не нужно показывать выбранные значения
            let self = this;
            let title,row,value,value_id;
            let wrapper = '';
            this.params.block_obj.find(".fltr_block." + blockActiveClass).each(function(){
                    title = $(this).find(".fltr_name").text();
                    row = '';
                    switch(true) {
                        case self.radioBlock($(this)):
                        case self.checkboxesBlock($(this)):
                            $(this).find(":checked").each(function(){
                                value_id = $(this).closest("[data-value-block]").data("valueBlock");
                                value = $(this).closest("[data-value-block]").find(".filter_value_title").text();
                                row += eFiltrChoicesRow.replaceAll('((value_id))', value_id).replaceAll('((value))', value);
                            })
                            //alert(choice);
                            break;
                        case self.selectBlock($(this)):
                            $(this).find("option:checked").each(function(){
                                value_id = $(this).data("valueBlock");
                                value = $(this).text();
                                value = value.replaceAll(/\(([^)]+)\)/g, '');
                                row += eFiltrChoicesRow.replaceAll('((value_id))', value_id).replaceAll('((value))', value);
                            })
                            break;
                        case self.sliderBlock($(this)):
                            value = $(this).find("[data-start-input]").val() + ' - ' + $(this).find("[data-finish-input]").val();
                            row += eFiltrChoicesRow.replaceAll('((value_id))', $(this).data("tvName")).replaceAll('((value))', value);
                            break;
                        default:
                            break;
                    }
                    if(row != '') {
                        wrapper += eFiltrChoicesOwner.replaceAll('((title))', title)
                            .replaceAll('((choices))', row)
                            .replaceAll('((tvname))', $(this).data("tvName"));
                    }
            })
            $(document).find("[data-filter-choices]").html(wrapper);
        },
        initSliders: function(){
            if(typeof eFiltrDisableDefaultSlider != "undefined") return;//слайдер отключен
            let self = this;
            this.params.block_obj.find(".fltr_block_slider [data-slider]").each(function(){
                let slider = $(this);
                let min, max, start, finish, startInput, finishInput, step, isRound, s;
                startInput = slider.closest(".fltr_inner_slider").find("input[data-start-input]");
                finishInput = slider.closest(".fltr_inner_slider").find("input[data-finish-input]");
                min = slider.data("min");
                max = slider.data("max");
                start = slider.data("start");
                finish = slider.data("finish");
                step = slider.data("step") || 1;
                isRound = slider.data("round") || 0;
                if(parseFloat(start) == parseFloat(min)) {
                    startInput.prop("disabled", "disabled");
                } else {
                    startInput.prop("disabled", "");
                }
                if(parseFloat(finish) == parseFloat(max)) {
                    finishInput.prop("disabled", "disabled");
                } else {
                    finishInput.prop("disabled", "");
                }
                s = noUiSlider.create(slider[0], {
                    start: [start, finish],
                    connect: true,
                    range: {
                        'min': min,
                        'max': max
                    },
                    tooltips: true,
                });
                s.on('change', function( values, handle ) {
                    startInput.val(isRound == 1 ? Math.round(values[0]) : values[0]);
                    finishInput.val(isRound == 1 ? Math.round(values[1]) : values[1]);

                    if(parseFloat(values[0]) == parseFloat(min)) {
                        startInput.prop("disabled", "disabled");
                    } else {
                        startInput.prop("disabled", "");
                    }
                    if(parseFloat(values[1]) == parseFloat(max)) {
                        finishInput.prop("disabled", "disabled");
                    } else {
                        finishInput.prop("disabled", "");
                    }
                    slider.closest("form").find("input:not(:disabled):first").submit();

                });
                s.on('update', function( values, handle ) {
                    startInput.val(isRound == 1 ? Math.round(values[0]) : values[0]);
                    finishInput.val(isRound == 1 ? Math.round(values[1]) : values[1]);
                });
            });
        }
        
    }
    $(function () {
        wnd.eFilter = new eFilter();
    })
}(window, jQuery);
