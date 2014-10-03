<?php
namespace universal;

class HtmlHlavicka extends Html {

	protected $nadpis;
	protected $popis;
	protected $zobrazIkonu = false;
	protected $beznyCss = true;
	protected $beznyJs = true;
	protected $mozneAdresareStylopisu = array('css','styles','styly');
	protected $mozneAdresareJavascriptu = array('js','javascript');
	protected $mozneUmisteniAdresaru;
	protected $hlavicka = '';

	public function __construct($znakovaSada = self::ZNAKOVA_SADA,$jazyk = self::JAZYK){
		parent::__construct($znakovaSada,$jazyk);
		$this->nastavMozneUmisteniAdresaru();
	}

	protected function nastavMozneUmisteniAdresaru(){
		$this->mozneUmisteniAdresaru = array(
			str_replace('\\','/',dirname(__FILE__).'/..')=>'../univerzal/'
			,dirname($_SERVER['SCRIPT_FILENAME'])=>''
		);
	}

	public function nastavNadpis($nadpis){
		$this->nadpis = $nadpis;
	}

	public function nastavPopis($popis){
		$this->popis = $popis;
	}

	public function nastavZobrazIkonu($zobrazIkonu){
		$this->zobrazIkonu = (bool)$zobrazIkonu;
	}

	public function nastavBeznyCss($beznyCss){
		$this->beznyCss = (bool)$beznyCss;
	}

	public function nastavBeznyJs($beznyJs){
		$this->beznyJs = $beznyJs;
	}

	public function rozsirHlavicku($rozsireni){
		$this->hlavicka .= "$rozsireni\n";
	}

	public function dejHlavicku(){
		$this->hlavicka .=  "\n		<meta http-equiv='Content-Language' content='$this->jazyk'/>";
		$this->hlavicka .= "\n		<meta http-equiv='Content-Type' content='text/html; charset=$this->znakovaSada'/>";
		if(isset($this->nadpis)){
			$this->hlavicka .= "\n		<title>$this->nadpis</title>";
		}
		if(isset($this->popis)){
			$this->hlavicka .= "\n		<meta name='description' content='$this->popis'/>";
		}
		if($this->beznyCss){
			foreach($this->mozneUmisteniAdresaru as $souborovaCestaKAdresariStylopisu=>$htmlCestaKAdresariStylopisu){
				foreach($this->mozneAdresareStylopisu as $moznyAdresarStylopisu){
					$stylopisy = FolderTools::dejSouboryZAdresare($souborovaCestaKAdresariStylopisu."/$moznyAdresarStylopisu",'css');//vyhledame soubor se styly v univerzal
					if($stylopisy){
						foreach($stylopisy as $css){
							$this->hlavicka .= "\n\t\t<link rel='stylesheet' type='text/css' href='$htmlCestaKAdresariStylopisu$moznyAdresarStylopisu/$css' media='all'/>";
						}
					}
				}
			}
		}
		if($this->beznyJs){
			foreach($this->mozneUmisteniAdresaru as $souborovaCestaKAdresari=>$htmlCestaKAdresari){
				foreach($this->mozneAdresareJavascriptu as $moznyAdresar){
					$javascripty = FolderTools::dejSouboryZAdresare($souborovaCestaKAdresari."/$moznyAdresar",'js');//vyhledame soubor se styly v univerzal
					if($javascripty){
						foreach($javascripty as $js){
							$this->hlavicka .= "\n\t\t<script type='text/javascript' src='$htmlCestaKAdresari$moznyAdresar/$js'></script>";
						}
					}
				}
			}
		}
		if($this->zobrazIkonu and file_exists(self::$materskyAdresar.'/grafika/favicon.ico')){
			preg_match_all('|/[[:alnum:]]+$|',str_replace('\\','/',self::$materskyAdresar),$cesta);
			$cesta = UtilitkyProPole::orezPole($cesta);
			$this->hlavicka .= "\n\t\t<link rel='shortcut icon' href='$cesta/grafika/favicon.ico'/>";
		}
		$this->hlavicka = "	<head>$this->hlavicka\n	</head>\n";
		return $this->hlavicka;
	}
}