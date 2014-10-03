function turn_other_options(nameOfChangedSelect,id) {//projde selecty v oblasti dane id (neni-li id nastaveno, tak v celem dokumentu) a prenastavi option u selectu, ktere maji stejne nastaveni jako select udany nazvem nameOfChangedSelect (prenastavuje jen prvni nalezenou duplicitu, s vice stejnymi duplicitami nepocita)
	
	if(nameOfChangedSelect){
		workSpace = get_element(id);
		selects = workSpace.getElementsByTagName('select');//z oblasti, kterou jsme nasli podle id, ziskame vsechny selecty, abychom v nich nasli prave zmeneny
		selectedOptions = new Array;
		nonSelectedOptions = new Array;
		var duplicity = false;//priznak, zda je nekde duplicita
		for(var i = 0; i < selects.length; i++){
			nonSelectedOptions[selects[i].name] = new Array;
			var options = selects[i].getElementsByTagName('option');//ziskame veskere option, ktere ve zpracovavanem selectu jsou
			for(var j=0; j < options.length; j++){//projdeme veskera option
				if(options[j].selected){
					for(nameOfSelectedSelect in selectedOptions){//projdeme predchozi vybrane options
						if(selectedOptions[nameOfSelectedSelect].value == options[j].value){//jestlize hodnotu soucasne option nese jiz jina nastavena option
							duplicity = true;//nasli jsme duplicitu a poznacime si tento nalez
						}
					}
					selectedOptions[selects[i].name] = options[j];//jeden select muze mit jen jednu nastavenou option
				}else{//jsme na option, ktera neni vybrana
					nonSelectedOptions[selects[i].name][nonSelectedOptions[selects[i].name].length] = options[j];
				}
			}
		}

		if(duplicity){//existuje duplicita - zkusime ji eliminovat
			eliminatingDuplicity :
			for(nameOfSelectedSelect in selectedOptions){//projdeme vsechny vybrane options
				if(nameOfSelectedSelect != nameOfChangedSelect){//nejsme na vyberu zmenenem uzivatelem
					if(selectedOptions[nameOfSelectedSelect].value == selectedOptions[nameOfChangedSelect].value){//hodnota option daneho selectu je stejna jako hodnota vybrana uzivatelem v jinem selectu - nasli jsme duplicitu
						for(var i = 0; i < nonSelectedOptions[nameOfSelectedSelect].length; i++){//projdeme kazdou volnou option toho selectu, u ktereho jsme zjistili duplicitu a zkusime najit volnou value
							usable = true;//na zacatku nastavime, ze hodnota volneho option je pouzitelna
							for(nameOfSelectedSelect2 in selectedOptions){//projdeme kazdou obsazenou moznost a proverime, zda neni jeji hodnota shodna s moznou volnou option, tedy zda se da pouzit pro eliminaci stare duplicity, aniz bychom vytvorili novou
								if(nameOfSelectedSelect2 != nameOfChangedSelect){//pokud nejsme na option uzivatelem zmeneneho selectu
									if(selectedOptions[nameOfSelectedSelect2].value == nonSelectedOptions[nameOfSelectedSelect][i].value){//hodnota potencialne volneho option testovaneho selectu je shodna s jiz vybranou hodnotou jineho selectu
										usable = false;//option tedy neni pouzitelna
										break;//ukoncime proverku potencialne volneho option, abychom presli na dalsi potencialne volny option
									}
								}
							}
							if(usable == true){//proverka pro potencialne volny option dopadla uspesne, nasli jsme volny value pro option
								for(indexOfSelects = 0; indexOfSelects < selects.length; indexOfSelects++){//projdeme selecty
									if(selects[indexOfSelects].name == nameOfSelectedSelect){//najdeme v nich ten, ve kterem chceme odstranit duplicitu
										var options = selects[indexOfSelects].getElementsByTagName('option');//ziskame veskere option, ktere v selectu s duplicitou jsou
										for(optionIndex = 0; optionIndex < options.length; optionIndex++){//projdeme options tohoto selectu
											if(options[optionIndex].value == nonSelectedOptions[nameOfSelectedSelect][i].value){//nasli tu option v selectu, ktera je volna
												options[optionIndex].selected = true;//nastavime volnou option na vybranou - automatika prohlizece jiz nastavi puvodni vyber na false
												break eliminatingDuplicity;//duplicita je odstranena nastavenim puvodne stejne hodnoty na prvni volnou, ukoncime eliminaci duplicity
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}