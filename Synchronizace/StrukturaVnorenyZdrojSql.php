<?php
	require_once(dirname(__FILE__).'/StrukturaSql.php');

	abstract class StrukturaVnorenyZdrojSql extends StrukturaSql {//tato trida je pro struktury, ktere nejsou konecnou strukturou zdroje, ale popisuji strukturu vnoreneho zdroje ve vnorenenem dotazu
	
		/*
		protected static $statickaHlavniStrukturaZdroje = false;//vyzaduje v dcerine tride uloziste pro nazev hlavni tridy/prvni instance, kterou jedinou povazuje za smerodatnou pri zjistovani LastUpdate
		*/
	
		public function __construct(){	
			$this->nastavHlavniStrukturaZdroje();//musi byt pred rodicovskym construktem!
			parent::__construct();
		}
		
		protected abstract function nastavHlavniStrukturaZdroje();
		
		/*
		protected function nastavHlavniStrukturaZdroje(){
			if(!isset(self::$statickaHlavniStrukturaZdroje)){
				trigger_error("Není zavedena statická vlastnost hlavniStrukturaZdroje, nutná pro správný běh instance dceřiné třídy ".get_class($this)." struktury vnořeného zdroje. Nelze pokračovat.",E_USER_ERROR);
			}elseif(empty(self::$statickaHlavniStrukturaZdroje)){//plni se jen pri prvni instanci teto tridy
				self::$statickaHlavniStrukturaZdroje = $this;
			}
			$this->hlavniStrukturaZdroje = self::$statickaHlavniStrukturaZdroje;//napric vsemi dcerinymi instancemi teto tridy zustava jedna hlavni struktura - ta, ktera byla prvni instanciovana
		}
		*/
		
	}
?>