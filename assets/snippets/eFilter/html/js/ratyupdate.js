function afterFilterComplete(f) {
	//alert('afterFilterComplete');
	jQuery(document).ready(function(t){
		t(".star-rating").raty({path:function(){return this.dataset.path||"/assets/snippets/star_rating/assets/img/"},starOn:function(){return this.dataset.on||"star-on.png"},starOff:function(){return this.dataset.off||"star-off.png"},starHalf:function(){return this.dataset.half||"star-half.png"},number:function(){return this.dataset.stars||5},score:function(){return this.dataset.rating||0},readOnly:function(){return 1==this.dataset.disabled},starType:function(){return this.dataset.type||"img"},click:function(s){var a=t(this),i=a.closest(".star-rating-container"),e=this.dataset.id;page_url=this.dataset.url||window.location.href;t.ajax({url:page_url,type:"get",data:{rid:e,vote:s},success:function(s){s?(i.find(".msg").remove(),s.success!==!0||s.error?a.raty("reload"):(i.find(".star-rating-votes").text(s.votes),i.find(".star-rating-rating").text(s.rating),a.raty("score",s.rating),a.raty("readOnly",!0)),a.append('<div class="msg">'+s.message+"</div>"),a.find(".mask").fadeOut(100,function(){t(this).remove()}),setTimeout(function(){i.find(".msg").fadeOut(1e3)},2e3)):alert("Unknown error. Try again later")},beforeSend:function(){a.append('<div class="mask" />')}})}});
	})
}

function afterFilterSend(msg) {
	//alert('afterFilterSend');
}

function beforeFilterSend(_form){
	//alert('beforeFilterSend');
}
