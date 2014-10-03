function getElementsByClassName(className,idOfSeekedThroughObject){

	className = className.toLowerCase();//dle XHTML by nazvy hledanych objektu mely byt malymi pismeny

	if(idOfSeekedThroughObject){
		var node = document.getElementById(idOfSeekedThroughObject);
	}else{//nedostali jsme id objektu, ve kterem chceme hledat prvky tridy
		var node = document.getElementsByTagName('body')[0];//prohledame cely dokument
	}
	
	var elementsWithClassName = new Array();//uloziste pro nalezene tridy
	var seekedThroughObjects = node.getElementsByTagName('*');//ziskame vsechny objekty z objektu, ktery chceme prohledat (vraci vsechny objekty, vcetne vnorenych)

	for(var i = 0;i < seekedThroughObjects.length; i++){
		if(seekedThroughObjects[i].className == className){
			elementsWithClassName.push(seekedThroughObjects[i]);
		}
	}
	
	return elementsWithClassName;

}