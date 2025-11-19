;
!function(wnd, $, undefined){
    var autoSubmit = wnd.eFiltrAutoSubmit||1;
    var useAjax = wnd.eFiltrAjax;
    var ajaxMode = wnd.eFiltrAjaxMode||1;
    var doChangeState = wnd.eFiltrChangeState||1;
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
            var self = this;
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
            if (typeof updateAll !== 'undefined') {
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
                history.pushState('', '', state);
                if ($("#changesortBy").length > 0) {
                    $("#changesortBy").attr("action", state);
                }
            }
        }
        
    }
    $(function () {
        wnd.eFilter = new eFilter();
    })
}(window, jQuery);
