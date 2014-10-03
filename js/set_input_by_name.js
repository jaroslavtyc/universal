function setInputByName(nazev) {

	elemetny = document.getElementsByName(nazev);
	for(i=0; i<elemetny.length; i++){
		if(elemetny[i].type == 'radio'){//u radia nastavujeme pouze checked
			if(!elemetny[i].checked){
				elemetny[i].checked = true;
			}
		}else if(elemetny[i].type == 'checkbox'){//u checkboxu prevracime vyber
			elemetny[i].checked = !elemetny[i].checked;
		}
	}

}