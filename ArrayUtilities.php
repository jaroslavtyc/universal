<?php
namespace universal;
class ArrayUtilities {
	
	private function __construct(){}//static methods only

	public static function dejKlicNaPozici($pole, $pozice, $orezPrebytecnePole = true){//nazev pro lenochy
		return self::dejKliceNaPozicich($pole, $pozice, $orezPrebytecnePole = true);
	}

	public static function dejKliceNaPozicich($pole, $pozice, $orezPrebytecnePole = true){
		$pozice = (array)$pozice;//pozici chceme v poli a serazenou podle hodnot vzestupne, abychom nemuseli kvuli kazde pozici prohledavat pole od zacatku
		sort($pozice);
		$klice = array();
		for($i = 0; $i < sizeof($pole); $i ++){
			if($i == current($pozice)){//pokud jsme na pozici pole, kterou hledame
				$klice[] = key($pole);//ulozime si klic z pole na teto poradove pozici
				next($pozice);//posuneme ukazatel v seznamu pozic na dalsi hledanou
			}
			next($pole);//posuneme ukazatel v prohledavanem poli na dalsi pozici
		}
		if($orezPrebytecnePole){
			return self::trimArray($klice);//pokud jsme nasli jen jeden klic, vratime ho samotny, bez pole
		}else{
			return $klice;
		}
	}
	
	public static function dejPrvkyNaPozicich($pole, $pozice, $orezPrebytecnePole = true){
		return self::dejPrvekNaPozici($pole, $pozice, $orezPrebytecnePole);
	}
	
	public static function dejPrvekNaPozici($pole, $pozice, $orezPrebytecnePole = true){
		$pozice = (array)$pozice;//pozici chceme v poli a serazenou podle hodnot vzestupne, abychom nemuseli kvuli kazde pozici prohledavat pole od zacatku
		sort($pozice);
		$polozky = array();
		for($i = 0; $i < sizeof($pole); $i ++){
			if($i == current($pozice)){//pokud jsme na pozici pole, kterou hledame
				$polozky[] = current($pole);//ulozime si klic z pole na teto poradove pozici
				next($pozice);//posuneme ukazatel v seznamu pozic na dalsi hledanou
			}
			next($pole);//posuneme ukazatel v prohledavanem poli na dalsi pozici
		}
		if($orezPrebytecnePole){
			return self::trimArray($polozky);//pokud jsme nasli jen jeden klic, vratime ho samotny, bez pole
		}else{
			return $polozky;
		}
	}

	public static function zarucPole($data){//sice lze pouzit pretypovani na array, ale to nelze pouzit napriklad pri praci s referencemi
		if(!is_array($data)){
			$data = array($data);
		}
		return $data;
	}
	
	public function nahradPrvekPrvkem($pole, $staryPrvek, $novyPrvek){//POZOR, hlida datovy typ
		if(is_array($pole)){
			foreach($pole as $index=>$prvek){
				if(is_array($prvek)){
					$prvek = self::nahradPrvekPrvkem($prvek, $staryPrvek, $novyPrvek);
				}elseif($prvek === $staryPrvek){
					$pole[$index] = $novyPrvek;
				}
			}
		}
		return $pole;
	}
	
	public static function udelejKlicZPozice($pole, $pozice = 0, $strojoveNazvyKlicu = false, $neprepisujPuvodniHodnotyKlicovaneCisly = false){//pokud je pozice ciselna, je pocitana od nuly(prvni pozice je 0)
		if(is_array($pole)){
			$noveKlicovanePole = array();
			foreach($pole as $index=>$radek){//pro kazdy radek pole
				if(is_int($pozice)){//pokud jsme pozici dostali jako poradi/cislo
					if(is_array($radek)){//jestlize je radek pole tvoren polem a my z nej muzeme vytahnout prvek na urcene pozici
						if($pozice < sizeof($radek)){//jestlize je vubec mozne najit prvek na dane pozici (je v moznem rozsahu)
							$poradi = 0;//inicializujeme nezavisle pocitadlo poradi prvku v radku
							foreach($radek as $indexPrvkuRadku=>$prvekRadku){//pro kazdy prvek radku
								if($poradi++ == $pozice){//jestlize jsme na pozici, kterou pozadujeme
									unset($radek[$indexPrvkuRadku]);//vymazeme prvek na teto pozici z radku
									/*if(isset($pole[$prvekRadku])){//pokud uz pozice s takovymto klicem existuje, smazeme ji!
										unset($pole[$prvekRadku]);
									}*///nejaka blbost...
									if($strojoveNazvyKlicu){
										$prvekRadku = UtilitkyProNazvy::udelejNazevPromenne($prvekRadku);
									}
									if($neprepisujPuvodniHodnotyKlicovaneCisly){//jestlize chceme pripadna data na stejnych klicich doplnovat i za cenu zmeny klice
										if(!isset($noveKlicovanePole[$prvekRadku])){
											$noveKlicovanePole[$prvekRadku] = array();
										}
										$noveKlicovanePole[$prvekRadku] = array_merge($noveKlicovanePole[$prvekRadku],$radek);//pouzijeme array merge, ktery neprepisuje hodnoty na stejnych ciselnych klicich, ale meni klice tak, aby se vyhl konfliktu
									}else{//prepisovani dat nam nevadi
										$noveKlicovanePole[$prvekRadku] = $radek;//natvrdo nastavime data klicovana podle vybraneho klice
									}
									break;//ukoncime prohledavani prvku radku
								}
							}
						}else{
							$noveKlicovanePole[$index] = $radek;//novy klic se nenasel, zkopirujeme radek
						}
					}else{
						$noveKlicovanePole[$index] = $radek;//neni z ceho delat klic, zkopirujeme hodnotu
					}
				}else{//pozice byla udana neciselne, nejspis retezcem
					if(is_array($pole[$index])){//pokud soucasny prvek pole je dalsi pole, tedy "radek"
						if(isset($pole[$index][$pozice])){//jestlize pozadovany prvek pro klic na pozadovane pozici existuje
							$novyKlic = $pole[$index][$pozice];//ulozime si novy klic
							/*if(isset($pole[$index][$novyKlic])){//jestlize je pozice uz obsazena
								unset($pole[$index][$novyKlic]);//promazeme ji
							}*/
							unset($pole[$index][$pozice]);//z puvodniho pole vymazeme prvek, ktery pouzijeme jako klic
							$noveKlicovanePole[$novyKlic] = $pole[$index];//ulozime si oklicovany radek
						}else{
							$noveKlicovanePole[$index] = $pole[$index];//novy klic se nenasel, zkopirujeme radek
						}
					}else{
						$noveKlicovanePole[$index] = $radek;//neni z ceho delat klic, zkopirujeme hodnotu
					}
				}
			}
			return $noveKlicovanePole;
		}
		return false;//pokud prevod selhal, vratime false
	}
	
	public static function nahradPrazdneRetezceNulou($pole){
		if(is_array($pole)){//menime jen pole
			foreach($pole as $index=>$prvek){
				if(is_array($prvek)){
					$pole[$index] = self::nahradPrazdneRetezceNulou($prvek);//rekurze
				}elseif($prvek === ''){
					$pole[$index]= 0;
				}
			}
		}
		return $pole;
	}
	
	public static function orezZbytecnaPoleDotazu($pole, $nahradPrazdnoNullem = false){//specializovano pro SQL
		$vysledek = $pole;
		if(is_array($pole)){
			switch(sizeof($pole)){
				case 1:
					$vysledek = $pole[0];//pole pouze obalovalo datoveho jedince, nebylo treba
					$vysledek = self::orezZbytecnaPoleDotazu($vysledek, $nahradPrazdnoNullem);//rekurze
					break;
				case 0:
					if($nahradPrazdnoNullem){
						$vysledek = 'NULL';
					}
					break;
				default;
			}
		}
		return $vysledek;
	}

	public static function trimArray($pole,$nahradPrazdnePolePrazdnymRetezcem = true){//rekurzivne zrusi pole, ktera obaluji jen jeden prvek a prazdna pole nahradi prazdnym stringem ""
		if(is_array($pole)){//menime jen pole
			foreach($pole as $index=>$prvek){//jdeme hloubeji
				$pole[$index] = self::trimArray($prvek);//rekurze - prohledani vsech vetvi pole az "na dren"
			}
			//rekurze ze zastavila, jsme tedy "na dne", na poslednim prvku pole, nebo nekterem vyssim pri opetovnem "zvedani se ze dna"
			if(sizeof($pole) === 0 and $nahradPrazdnePolePrazdnymRetezcem){//prvek bylo prazdne pole, nahradime pole pradnym stringem
				$pole = "";
			}elseif(sizeof($pole) === 1){//prvek v poli je stale array, ale obsahuje uz jen jeden prvek
				$pole = $pole[key($pole)];//array kolem prvku ho pouze obalovalo, nebylo tedy treba
			}
		}
		return $pole;
	}
	
	public static function dejPolozkuNaZacatek($polozka, $pole){
		array_unshift($pole, $polozka);
		return $pole;
	}

	public static function dejPolozkuNaKonec($polozka, $pole){
		$pole[] = $polozka;
		return $pole;
	}
	
	public static function dejKopiiPole($pole){//vytvori presnou kopii pole a to vrati - nikoli referenci, jak to ma PHP ve zvyku
		$novePole = array();
		foreach($pole as $index=>$prvek){
			$novePole[$index] = $prvek;
		}
		return $novePole;
	}
	
	public static function rozprostriPole($poleKRozprostreni, $puvodniPole = array(), $posledniPolozkaJakoHodnota=false){//do prvniho pole vlozi strukturu vicerozmerneho pole, sestaveneho dle polozek ve druhem poli s ohledem na jejich poradi(tedy poradi, ve kterem byly do pole vkladany a na ktere reaguje ukazatel pole)
	//POZOR - pokud v puvodnim poli existuje polozka, klicovana jednim z polozek v poli rozprostreni presne na te pozici, kde by jinak vzniklo prazdne pole, klicovane polozkou po rozprostreni, tato polozka je zachovana a rozprostirani se PRERUSI. Napriklad puvodni pole: Array([deda]=>Array([otec]=>Honza)) a pole k rozprostreni Array([0]=>deda[1]=>otec[2]=>syn) vytvori Array([deda]=>Array([otec]=>Honza)) - jak je videt, obsazena pozice tam, kde by funkce chtela nastrcit pole, zrusi prubeh
		if(is_array($puvodniPole) and is_array($poleKRozprostreni) and sizeof($poleKRozprostreni)>0){//pokud ma rozprostreni smysl
			do{//dokud prvky z pole k rozprostreni jsou dalsi pole, prochazime je a hledame hodnoty
				$klicPrvnihoPrvku = key($poleKRozprostreni);
				$prvniPrvek = current($poleKRozprostreni);//vytahneme si prvni prvek z pole k rozprostreni
				unset($poleKRozprostreni[key($poleKRozprostreni)]);//odebereme tento prvek z puvodniho pole - uz zde neni zadouci
				if(is_array($prvniPrvek)){//jestlize prvni prvek neni vhodny jako klic, tedy je to pole
					if(!isset($puvodniPole[$klicPrvnihoPrvku])){
						$puvodniPole[$klicPrvnihoPrvku] = array();
					}
					$puvodniPole[$klicPrvnihoPrvku] = self::rozprostriPole($prvniPrvek, $puvodniPole[$klicPrvnihoPrvku], $posledniPolozkaJakoHodnota);//budeme ho prohledavat az do mist, kde budou rozprostiratelna data
				}else{
					if(!isset($puvodniPole[$prvniPrvek])){//pokud v puvodnim (nutne jednorozmernem) poli neni zadna polozka klicovana podle prvni hodnoty z pole k rozprostreni, vytvorime takovou
						if($posledniPolozkaJakoHodnota and (sizeof($poleKRozprostreni) === 1)){//pokud posledni prvek chceme pouzit jako hodnotu na dne pole a pristi prvek je posledni
							$puvodniPole[$prvniPrvek] = current($poleKRozprostreni);//dame ho na dno pole - v pristim cyklu se uz nic dit nebude, prvni podminka o $puvodniPole[$prvniPrvek] jako poli zastavi praci
						}else{
							$puvodniPole[$prvniPrvek] = array();
						}
					}
					$puvodniPole[$prvniPrvek] = self::rozprostriPole($poleKRozprostreni, $puvodniPole[$prvniPrvek], $posledniPolozkaJakoHodnota);//rekurze se zkracenym polem k rozprostreni a posunutout hloubkou pouvodniho pole
				}
			}while(is_array($prvniPrvek));
		}
		return $puvodniPole;//vratime upravene pole - 
	}

	public static function rozprostriPoleSPrvkem($poleKRozprostreni, $puvodniPole = array(), $prvek){//prida prvek na dno pole, jehoz struktura - pokud dosud neexistuje - je vytvorena podle pole k rozprostreni; prvek je dan na dno vzdy, pricemz prepise pripadny predchozi!
		if(is_array($puvodniPole) and is_array($poleKRozprostreni) and sizeof($poleKRozprostreni)>0){//pokud ma rozprostreni smysl
			$prvniPrvek = current($poleKRozprostreni);//vytahneme si prvni prvek z pole k rozprostreni
			unset($poleKRozprostreni[key($poleKRozprostreni)]);//odebereme tento prvek z puvodniho pole k rozprostreni - uz zde neni zadouci
			if(!isset($puvodniPole[$prvniPrvek])){//pokud v puvodnim (nutne jednorozmernem) poli neni zadna polozka klicovana podle prvni hodnoty z pole k rozprostreni, vytvorime takovou
				$puvodniPole[$prvniPrvek] = array();
			}
			$puvodniPole[$prvniPrvek] = self::rozprostriPoleSPrvkem($poleKRozprostreni, $puvodniPole[$prvniPrvek], $prvek);//rekurze se zkracenym polem k rozprostreni a posunutout hloubkou pouvodniho pole
			return $puvodniPole;//vratime upravene pole - 
		}else{//pokud uz jsme na dne, vratime prvek (ktery se tak ulozi na zminenem dne)
			return $prvek;
		}
	}
	
	public static function rozprostriANaplnPole($poleKRozprostreni, $puvodniPole, $udajKVyplneni){
		$upravenePole = self::rozprostriPole($poleKRozprostreni, $puvodniPole);
		return self::naplnPole($upravenePole, $udajKVyplneni);
	}

	public static function rozprostriANacpiPole($poleKRozprostreni, $puvodniPole, $udajKVyplneni){
		$upravenePole = self::rozprostriPole($poleKRozprostreni, $puvodniPole);
		return self::nacpiPole($upravenePole, $udajKVyplneni);
	}

	public static function rozprostriANacpiPoleRozprsknutim($poleKRozprostreni, $puvodniPole, $udajKVyplneni){
		$upravenePole = self::rozprostriPole($poleKRozprostreni, $puvodniPole);
		return self::nacpiPoleRozprsknutim($upravenePole, $udajKVyplneni);
	}
	
	public static function naplnPole($pole, $udajKVyplneni){//naplni pole pouze v pripade, ze dno pole je neobsazene!
		if(is_array($pole)){//pokud je co vyplnovat
			if(sizeof($pole) === 0){//pokud je pole prazdne
				$pole[] = $udajKVyplneni;//prida se do nej udaj
			}else{
				foreach($pole as $index=>$prvek){
					$pole[$index] = self::naplnPole($prvek, $udajKVyplneni);//rekurze
				}
			}
		}
		return $pole;
	}

	public static function nacpiPole($pole, $udajKVyplneni){//POZOR - nezajima ho, zda v miste, kam udaj pridava, uz nahodou stejny neni, klidne vytvori duplikat - ovsem na dno pole prida udaj jedine pokud jich tam pak nebude vic jak dva
		if(is_array($pole)){//pokud je co vyplnovat
			if(sizeof($pole) === 0){//jestlize je pole prazdne
				$pole[] = $udajKVyplneni;
			}else{
				foreach($pole as $index=>$prvek){
					if(sizeof($pole) === 1 and !is_array($prvek)){//pole ma jeden prvek a tento prvek neni polem
						$pole[] = $udajKVyplneni;
					}else{
						$pole[$index] = self::nacpiPole($prvek, $udajKVyplneni);//rekurze
					}
				}
			}
		}
		return $pole;
	}
	
	public static function nacpiPoleRozprsknutim($pole, $udajKVyplneni){//POZOR - nezajima ho, zda v miste, kam udaj pridava, uz nahodou stejny neni, klidne vytvori duplikat; Na rozdil od nacpiPole pouziva tato funkce array_merge tak, ze obvykle nerozsiruje strukturu pole
		if(is_array($pole)){//pokud je co vyplnovat
			if(!is_array($udajKVyplneni)){
				$udajKVyplneni = array($udajKVyplneni);//obalime ho polem
			}
			if(sizeof($pole) === 0){//jestlize je pole prazdne
				$pole = $udajKVyplneni;
			}else{
				foreach($pole as $index=>$prvek){
					if(sizeof($pole) === 1 and !is_array($prvek)){//pole ma jeden prvek a tento prvek neni polem
						$pole = array_merge($pole,$udajKVyplneni);
					}else{
						$pole[$index] = self::nacpiPoleRozprsknutim($prvek, $udajKVyplneni);//rekurze
					}
				}
			}
		}
		return $pole;
	}
	
	public static function okopirujStrukturuPole($pole){
		if(is_array($pole)){
			foreach($pole as $index=>$prvek){
				$pole[$index] = self::okopirujStrukturuPole($prvek);
			}
			return $pole;
		}else{
			return '';
		}
	}
	
	public static function prunikStrukturPoli($pole1, $pole2){
		$prunik = array();
		if(is_array($pole1) and is_array($pole2)){
			foreach($pole1 as $index1=>$prvek1){
				if(isset($pole2[$index1])){
					$prunik[$index1] = self::prunikStrukturPoli($prvek1, $pole2[$index1]);
				}
			}
		}
		return $prunik;
	}
	
	public static function sjednoceniStrukturPoli(){
		$sjednoceni = array();
		$pocetPoli = func_num_args();
		for($i = 0; $i < $pocetPoli; $i++){
			if(is_array(func_get_arg($i))){
				foreach(func_get_arg($i) as $index=>$prvek){
					if(!isset($sjednoceni[$index])){
						$sjednoceni[$index] = self::sjednoceniStrukturPoli($prvek);
					}else{
						$sjednoceni[$index] = self::doplnPolePolem($sjednoceni[$index],self::sjednoceniStrukturPoli($prvek));
					}
				}
			}
		}
		return $sjednoceni;
	}

	public static function sjednoceniStrukturPoliPonechaniHodnot(){//POZOR!!, pokud na indexu doplnovaneho pole, za kterym jeste pokracuje struktura doplnujiciho pole, uz neco je, nic se z doplnujiciho pole nedoplni
		$sjednoceni = array();
		$pocetPoli = func_num_args();
		if($pocetPoli > 1){
			for($i = 0; $i < $pocetPoli; $i++){
				if(is_array(func_get_arg($i))){
					foreach(func_get_arg($i) as $index=>$prvek){
						if(!isset($sjednoceni[$index])){
							$sjednoceni[$index] = self::sjednoceniStrukturPoliPonechaniHodnot($prvek);
						}else{
							$sjednoceni[$index] = self::doplnPolePolem($sjednoceni[$index],self::sjednoceniStrukturPoliPonechaniHodnot($prvek));
						}
					}
				}
			}
		}else{
			return func_get_arg(0);
		}
		return $sjednoceni;
	}
	
	public static function doplnPolePolemCiHodnotou($doplnovanePole, $plneni){
		if(is_array($doplnovanePole)){
			if(is_array($plneni)){
				foreach($plneni as $indexDoplnujicihoPole=>$prvekDoplnujicihoPole){
					if(!isset($doplnovanePole[$indexDoplnujicihoPole])){//takovato struktura jeste v poli1 neni, dodame ji i s navazujicimi daty
						$doplnovanePole[$indexDoplnujicihoPole] = $prvekDoplnujicihoPole;
					}else{//takovato struktura zde jiz je, pujdeme tedy hloubs pomoci rekurze
						$doplnovanePole[$indexDoplnujicihoPole] = self::doplnPolePolem($doplnovanePole[$indexDoplnujicihoPole],$prvekDoplnujicihoPole);
					}
				}
			}else{
				return $plneni;
			}
		}
		return $doplnovanePole;//vratime doplnovanePole, zmenene v pripade, ze byly vhodne podminky pro prenos struktury
	}
	
	public static function doplnPolemPole($doplnujiciPole, $doplnovanePole = array()){//strukturu s hodnotami na dne, kterou doplnujiciPole ma ale doplnovanePole ne, k poli1 pridame
		return self::doplnPolePolem($doplnovanePole, $doplnujiciPole);
	}

	public static function doplnPolePolem($doplnovanePole, $doplnujiciPole){//strukturu s hodnotami na dne, kterou doplnujiciPole ma ale doplnovanePole ne, k poli1 pridame
		if(is_array($doplnujiciPole) and is_array($doplnovanePole)){//jestlize jsou podminky k prenaseni struktury (pokud by bylo doplnovanePole nepole, bez teto podminky bychom ho prepsali; pokud by bylo nepole doplnujiciPole, pak bychom bez teto podminky cpali tam, kam patri pole, nevhodny typ)
			foreach($doplnujiciPole as $indexDoplnujicihoPole=>$prvekDoplnujicihoPole){
				if(!isset($doplnovanePole[$indexDoplnujicihoPole])){//takovato struktura jeste v poli1 neni, dodame ji i s navazujicimi daty
					$doplnovanePole[$indexDoplnujicihoPole] = $prvekDoplnujicihoPole;
				}else{//takovato struktura zde jiz je, pujdeme tedy hloubs pomoci rekurze
					$doplnovanePole[$indexDoplnujicihoPole] = self::doplnPolePolem($doplnovanePole[$indexDoplnujicihoPole],$prvekDoplnujicihoPole);
				}
			}
		}
		return $doplnovanePole;//vratime doplnovanePole, zmenene v pripade, ze byly vhodne podminky pro prenos struktury
	}
	
	public static function trimArrayNejnizsihoStupne($pole){//zrusi pole, ktere obsahuje uz jen jednu hodmotu a tou neni pole
		if(is_array($pole)){//menime jen pole
			if(sizeof($pole) === 1 and !is_array($pole[0])){//prvek v poli je stale array, ale obsahuje uz jen jeden prvek a ten neni pole
				$pole = $pole[0];//array kolem prvku ho pouze obalovalo, nebylo tedy treba
			}else{
				foreach($pole as $index=>$prvek){//jdeme hloubeji
					$pole[$index] = self::trimArrayNejnizsihoStupne($prvek);//rekurze - prohledani vsech vetvi pole az "na dren"
				}
			}
		}
		return $pole;
	}
	
	public static function smazPrvkySPrazdnymiRetezci($pole){//nemaze prazdna pole, jen prvky s prazdnym retezcem
		if(is_array($pole)){//pokud jde o pole
			foreach($pole as $index=>$p){
				$pole[$index] = self::smazPrvkySPrazdnymiRetezci($p);
				if($pole[$index] == ''){
					unset($pole[$index]);
				}
			}
		}
		return $pole;	
	}
	
	public static function smazPrazdnaPole($pole){
		return self::smazPrazdneInformace($pole,false);
	}

	public static function smazPrazdneInformace($informace,$odstranPrazdneRetezce = true,$ponechavejNullHodnoty = false){//rekurzivne smaze pole, ktera nic neobsahuji - provede "splasknuti bublin"; take maze prazdne stringy; neprevadi null, neni-li zadano jinak
		if(is_array($informace)){//pokud jde o pole
			foreach($informace as $index=>$i){//pro vsechny prvky pole
				$informace[$index] = self::smazPrazdneInformace($i,$odstranPrazdneRetezce,$ponechavejNullHodnoty);//rekurzivne, tzn. i pro prvky v podpolich
				if(sizeof($informace[$index]) == 0){//nasli jsme zcela prazdne pole, zlikvidujeme ho
					if(!$ponechavejNullHodnoty or ($ponechavejNullHodnoty and $i !== null)){
						unset($informace[$index]);
					}
				}
			}
			if(sizeof($informace) == 0){//nasli jsme zcela prazdne pole, zlikvidujeme ho
				unset($informace);
			}
		}elseif($odstranPrazdneRetezce and $informace === ""){//krom prazdneho array muze byt polozka take proste prazdna
			unset($informace);
		}
		if(isset($informace)){
			return $informace;
		}else{
			return null;
		}
	}

	public static function trimArrayAndDeleteEmpty($pole){//rekurzivne smaze pole, ktera nic neobsahuji, zrusi pole, ktera obaluji jen jeden prvek a prazdna pole nahradi prazdnym stringem ""
		return self::smazPrazdneInformace(self::trimArray($pole));
	}
	
	public static function seradPoleNaUrovniDleKlice($pole, $uroven=1){
		if(is_array($pole)){//jestlize je co radit
			if(--$uroven === 0){//jsme na urovni, kde chceme pole radit
				ksort($pole);//seradime pole podle klicu
			}else{
				foreach($pole as $index=>$prvek){
					$pole[$index] = self::seradPoleNaUrovniDleKlice($prvek, $uroven);
				}
			}
		}
		return $pole;
	}	
}
