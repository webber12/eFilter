$(document).ready(function(){

$(document).on("change", "form#eFiltr input, form#eFiltr select", function(e) {
    if (window.eFiltrAutoSubmit && eFiltrAutoSubmit == '1') {
        $("form#eFiltr").submit();
    }
})

$(document).on("submit", "form#eFiltr", function(e){
	if (window.eFiltrAjax && eFiltrAjax == '1') {
		e.preventDefault();
		var _form = $(this);
		var data2 = _form.serialize() + '&no_ajax_for_star_rating=1';
		var action = _form.attr("action");
		$.ajax({
			url: action,                                   
			data: data2,
			type: "GET",   
			beforeSend:function(){
				$("#eFiltr").css({'opacity':'0.5'});
				$("#eFiltr_results_wrapper .eFiltr_loader").show();
				$("#eFiltr_results").css({'opacity':'0.5'});
				if (typeof(beforeFilterSend) == 'function') {
					beforeFilterSend(_form);
				}
			},                   
			success: function(msg){
				if (typeof(afterFilterSend) == 'function') {
					afterFilterSend(msg);
				}
				var new_form = $(msg).find("#eFiltr").html();
				$("#eFiltr").html(new_form).css({'opacity':'1'});
				var new_result = $(msg).find("#eFiltr_results").html();
				$("#eFiltr_results_wrapper .eFiltr_loader").hide();
				$("#eFiltr_results").html(new_result).css({'opacity':'1'});
				if (typeof(afterFilterComplete) == 'function') {
					afterFilterComplete(_form);
				}
			}
		})
	}
})
/* ajax-paging of eFilter Results */
$(document).on("click", ".paginate a", function(e){
	if (window.eFiltrAjax && eFiltrAjax == '1') {
		e.preventDefault();
		var _form = '';
		var data2 = '&no_ajax_for_star_rating=1';
		var action = $(this).attr("href");
		$.ajax({
			url: action,                                   
			data: data2,
			type: "POST",   
			beforeSend:function(){
				$("#eFiltr_results").css({'opacity':'0.5'});
				if (typeof(beforeFilterSend) == 'function') {
					beforeFilterSend(_form);
				}
			},                   
			success: function(msg){
				if (typeof(afterFilterSend) == 'function') {
					afterFilterSend(msg);
				}
				var new_result = $(msg).find("#eFiltr_results").html();
				$("#eFiltr_results").html(new_result).css({'opacity':'1'});
				if (typeof(afterFilterComplete) == 'function') {
					afterFilterComplete(_form);
				}
			}
		})
	}
})

})