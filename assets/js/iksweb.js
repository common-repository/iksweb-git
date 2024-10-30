/*
Author URI: https://plugin.iksweb.ru/
Author: Сергей Князев
*/

(function($){
	
	$("ul.adm-detail-tabs-block").on("click", "li:not(.active)", function() {
      $(this)
        .addClass("active")
        .siblings()
        .removeClass("active")
        .closest("div.tabs")
        .find("div.adm-detail-content-wrap")
        .removeClass("active")
        .eq($(this).index())
        .addClass("active");
        
        history.pushState(null, null, "#"+$(this).attr('data-id'));	
    });

    if(window.location.hash) {
    	
		var hash = $('ul.adm-detail-tabs-block li[data-id="'+window.location.hash.split('#')[1]+'"]');
		
		hash
		  .addClass("active")
		  .siblings()
		  .removeClass("active")
		  .closest("div.tabs")
		  .find("div.adm-detail-content-wrap")
		  .removeClass("active")
		  .eq(hash.index())
		  .addClass("active");
	}
    
	jQuery(document).on('click','[data-massage]',function() {
	    $($(this).attr("href")+' span').html($(this).attr("data-massage"));
	});
	
	/* MODAL POOP */
	jQuery(document).on('click','a[data-modal=open]',function() {
		
		$('.modal').remove();
		
		var app = '';
		app += '<div class="modal active">';
		app += '	<div class="modal-content" id="modal-content">';
		app += '		<div class="modal-head">';
		app += '			<div class="modal-name"></div>';
		app += '			<div class="modal-close" data-modal="close"></div>';
		app += '		</div>';
		app += '		<div class="modal-dialog-br"><div class="modal-dialog"></div></div>';
		app += '	</div>';
		app += '	<div class="modal-shadow" data-modal="close"></div>';
		app += '</div>';
		
	    $('body').append(app);
	    
		var name = $(this).attr("data-modal-name");
		if(name){$('.modal .modal-name').html(name)}
		
		var href = $(this).attr("href");
		$(href).clone(true).unwrap().appendTo('.modal .modal-dialog');
	    
	    return false;
	});	

	jQuery(document).on('click','.modal [data-modal=close]',function() {
	    $('.modal').remove();
	});

	// Выводи ID картинок
	(function($){
		
		function checkLoaded(){
			$('.media-frame-content ul.attachments li.attachment').each(function(i){
			    var ID = $(this).attr('data-id');
				$(this).append( "<span class='media-num'>ID: "+ID+"</span>" );
			});
		}
		setTimeout(function(){
			checkLoaded();
		}, 500);
		
	})(jQuery);

})(jQuery);


