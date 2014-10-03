function setInputById(id) {

	element = document.getElementById(id);
	if(element.type == 'radio'){
		if(!element.checked){
			element.checked = true;
		}
	}
	if(element.type == 'checkbox'){
		element.checked = !element.checked;
	}

}