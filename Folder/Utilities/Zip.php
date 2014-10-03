<?php
	class Zip extends Trida{
	
	protected $zip;
	protected $archiv;
	protected $poradi = 0;
	
	public function __construct($plnaCestaKPracovnimuSouboru = FALSE){
		parent::__construct();
		$this->zip = new ZipArchive();
		if($plnaCestaKPracovnimuSouboru === FALSE){
			$plnaCestaKPracovnimuSouboru = tempnam(sys_get_temp_dir(),'zip');
		}
		$this->archiv = $plnaCestaKPracovnimuSouboru;
		if(!$this->zip->open($this->archiv,ZIPARCHIVE::CREATE)){
			trigger_error("Nepodařilo se otevřít archiv.",E_USER_NOTICE);
		}
	}
	
	public function addFile($plnaCestaKSouboru, $novyNazevSouboru = FALSE, $priponaNovehoNazvuSouboru = FALSE, $doAdresare = FALSE, $datumKNazvu = FALSE){//neprijima resource jako soubor a nestara se o to, zda jde o soubor docasny a zda byl uzavren
		$this->poradi++;
		if($novyNazevSouboru === FALSE){
			$novyNazevSouboru = (string)$this->poradi;
		}
		if($priponaNovehoNazvuSouboru !== FALSE){
			if(strpos('.',$priponaNovehoNazvuSouboru) !== 0){//pokud pripona nezacina na tecku, dodame ji
				$priponaNovehoNazvuSouboru = '.'.$priponaNovehoNazvuSouboru;
			}
		}
		if($datumKNazvu){
			$novyNazevSouboru .= date('_j_n_Y');
		}
		if($doAdresare !== FALSE){
			$doAdresare = str_replace('\\','/',$doAdresare);
			$doAdresare = UtilitkyProNazvy::srazOpakovanyZnak($doAdresare,'/',1,true);
			if(strrpos($doAdresare,'/') === (strlen($doAdresare)-1)){//pokud posledni znak je /
				if(strlen($doAdresare) === 1){//pokud cely nazev adresare tvorilo pouze lomitko, zrusime ho (neni treba uvadet korenovy adresar)
					$doAdresare = FALSE;
				}
			}else{
				$doAdresare .= '/';//adresar nekoncil lomitkem, dodame ho
			}
			if($doAdresare !== FALSE){//jestlize je pridani adresarove struktury do zipu stale aktualni, pridame ho (pokud tam uz je, nic se nedeje)				
				$this->zip->addEmptyDir($doAdresare);
			}
		}
		$this->zip->addFile($plnaCestaKSouboru,$doAdresare.$novyNazevSouboru.$priponaNovehoNazvuSouboru);
	}
	
	public function addFiley($plneCestyKSouborum,$novyNazevSouboru = FALSE,$priponaNovehoNazvuSouboru = FALSE,$doAdresare = FALSE,$datumKNazvu = FALSE){//neprijima resource jako soubor a nestara se o to, zda jde o soubor docasny a zda byl uzavren
		foreach((array)$plneCestyKSouborum as $plnaCestaKSouboru){
			if(is_array($plnaCestaKSouboru)){
				$this->addFiley($plnaCestaKSouboru,$novyNazevSouboru,$priponaNovehoNazvuSouboru,$doAdresare,$datumKNazvu);
			}else{
				$novyNazevSouboru .= (string)($this->poradi+1);
				$this->addFile($plnaCestaKSouboru,$novyNazevSouboru,$priponaNovehoNazvuSouboru,$doAdresare,$datumKNazvu);
			}
		}
	}
	
	public function dejSoubory(){
		$this->zip->close();
		return $this->archiv;
	}
		
	public function ukazSoubory($nazevExportu = FALSE, $datumKNazvuExportu = true, $znakovaSada = PripojeniMysql::ZNAKOVA_SADA){
		$PraceSWebem = new PraceSWebem($znakovaSada);
		if($nazevExportu === FALSE){
			$nazevExportu = 'export';
		}
		$PraceSWebem->pripravExportDoSouboru($nazevExportu,$datumKNazvuExportu,'zip');//pripravime prohlizec na prijeti souboru
		$archiv = $this->dejSoubory();
		$archiv = fopen($archiv,'r');
		while($radek = fgets($archiv)){
			echo($radek);
		}
		fclose($archiv);
	}
}
?>