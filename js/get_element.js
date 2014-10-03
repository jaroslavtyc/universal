function get_element(id) {

	if(id){
		var element = document.getElementById(id);
	}else{//nedostali jsme id objektu, ve kterem chceme hledat prvky tridy
		var element = document.getElementsByTagName('body')[0];//prohledame cely dokument
	}
	
	return element;

}