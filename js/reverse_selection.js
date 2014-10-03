function reverseSelection(prevratTyp,prevratId,ignorujTridu,ignorujId) {

	if(prevratId){
		var inputs = document.getElementById(prevratId); /*moznosti*/
	}else{
		var inputs = document.getElementsByTagName('body');//nedostali jsme konkretni id objektu, pouzijeme cely dokument
		inputs = inputs[0];
	}
	for(var i = 0; i < inputs.length; i++){
		if(inputs[i].type == prevratTyp){//pokud input type je zadany typ
			if((!ignorujTridu || ignorujTridu != inputs[i].className) && (!ignorujId || inputs[i].id != ignorujId)){//a budto chceme prevratit vyber na vsech polickach (v pripade, ze uzivatel klikl na text, nikoli na policko tlacitkoOznaceniVseho), nebo nechceme prevratit vyber na policku tlacitkoOznaceniVseho, ale na tomto policku nejsme(to je pro pripad, kdy uzivatel klikne presne na policko tlacitkoOznaceniVseho)
				inputs[i].checked = !inputs[i].checked;//prevratime vyber
			}
		}
	}
	nastavTextPrevratu();

}