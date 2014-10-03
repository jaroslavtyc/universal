function hiding(id,posun) { 

	x = document.body.scrollLeft;
	y = document.body.scrollTop;
	name = document.getElementById(id).className;
	if(name == 'neviditelny'){
		name = 'viditelny';
	}else if(name == 'viditelny'){
		name = 'neviditelny';
	}
	document.getElementById(id).className=name;
	if(name == 'neviditelny'){
		posun = (-1)*posun;
	}
	resetScrollPosition(x,y+posun);
}