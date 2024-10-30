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

})(jQuery);