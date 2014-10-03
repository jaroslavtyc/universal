<?php
	class HtmlStranka extends HtmlHlavicka {
		
		protected $znakovaSada;
		protected $jazyk;
		protected $urciXml = false;
		protected $parametryHtml;
		protected $parametryBody;
		
		public function __construct($znakovaSada = self::ZNAKOVA_SADA, $jazyk = self::JAZYK){
			parent::__construct($znakovaSada,$jazyk);
		}
		
		public function __destruct(){
			$this->zobrazKonecDokumentu();
		}
		
		public function nastavUrciXml($urciXml){
			$this->urciXml = $urciXml;
		}
		
		public function nastavParametryHtml($parametryHtml){
			$this->parametryHtml = $parametryHtml;
		}
		
		public function nastavParametryBody($parametryBody){
			$this->parametryBody = $parametryBody;
		}

		public function dejXmlVersion(){
			$xmlVersion = "<?xml version='1.0' encoding='$this->znakovaSada' ?>";
			return $xmlVersion;
		}
		
		public function dejZacatekDokumentu(){
			$dokument = '';
			if($this->urciXml){
				$dokument .= $this->dejXmlVersion()."\n";
				$dokument .= " xml:lang='$this->jazyk'";
			}
			$dokument .= self::DOCTYPE."\n";
			$dokument .= "<html ".self::XMLNS." $this->parametryHtml lang='$this->jazyk'>\n";
			$dokument .= $this->dejHlavicku();
			$dokument .= "	<body $this->parametryBody>\n";//nebudeme-li vypisovat hlavicku, nevypiseme ani tag body
			return $dokument;
		}
		
		public function zobrazZacatekDokumentu(){
			echo($this->dejZacatekDokumentu());
		}
		
		public function dejKonecDokumentu(){
			$konec = "	</body>\n</html>";
			return $konec;
		}
		
		public function zobrazKonecDokumentu(){
			echo($this->dejKonecDokumentu());
		}
	}
?>