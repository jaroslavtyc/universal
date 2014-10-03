$("div#checks div.checks").each(function(){
	$(this).children("div.js").remove();//removing javascript requirement text
	if ($(this).children("div:visible").size() == 0) {
		$("div#checks div." + $(this).attr('class')).remove();//removing whole requirement text
	}
});
if ($("div#checks div:visible").size() == 0) {
	$("div#checks").remove();
}

if ($("span#js-required").css('display') == 'none') {//cross-technology check - if there is no display style, so css does not work and we will remove whole content
	$("span#js-required").css("display","inherit");//javascript requirements were met, main content should be shown
}

if (!$("span#css-required").css('display')) {//cross-technology check - if there is no display style, so css does not work and we will remove whole content
	$("span#css-required").remove();
}
