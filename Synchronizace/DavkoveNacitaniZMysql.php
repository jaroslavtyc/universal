<?php
	require_once(dirname(__FILE__).'/DavkoveNacitaniZSql.php');

	class DavkoveNacitaniZMysql extends DavkoveNacitaniZSql{

		public function __construct($Pripojeni, $dotaz, $radkuNaDavku){
			parent::__construct($Pripojeni, $dotaz, $radkuNaDavku);
		}
		
		protected function dejUpravenyDotazNaLimit(){//tato funkce je volana pouze materskou tridou
			return $this->dotaz." LIMIT $this->od,$this->radkuNaDavku";
		}
	}
?>