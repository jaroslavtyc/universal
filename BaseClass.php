<?php
namespace universal;

abstract class BaseClass {//Trida je hlavne pro pristup k vlastnostem trid, ktere chceme jen pro cteni a nechceme pro kazdou delat zvlast funkci - POZOR! volani constructoru teto tridy (rodicovske) musi byt uplne prvni akce, kterou dcerina trida udela, jinak dojde k volani funkci get a set (NEEXISTUJICI INSTANCE) bez inicializace doprovodnych vlastnosti a trid
 //POZOR! AutoLoad je dcerinou tridou Tridy a funkce autoload je definovana v autoload.php driv, nez je vytvorena isntance Autoload a proto v okamziku, kdy je hledana neznama funkce/trida, pokusi se system pracovat s neexistujicim objektem. Proto MUSI vsechny skripty, ktere Trida vyuziva, mit v sobe bezchybne require!!

	private $readable = array();
	private $writable = array();
	private $allowMagicGetToMethod = TRUE;
	private $useGetMethodToAccessProperty = TRUE;
	private $allowNullAfterFilling = FALSE;

	/**
	 *
	 * @param string $trida name of class to get structure around
	 * @param bool $odRodicePoPotomka
	 * @return array
	 */
	public function getClassesStructure($trida = FALSE, $odRodicePoPotomka = TRUE) {//je-li pozadovano serazeni vysledku od rodice po potomka, bude seznam vracen v tomto poradi, jinak bude serazen obracene, od nejnizsiho potomka po nejvyssiho rodice
		$seznamTrid = array();//uloziste pro nazvy trid, ktere jsou rodicovske pro aktualni tridu
		if($trida === false){//nedostali jsme ke zpracovani jinou tridu, zamerime se na soucasnou
			$trida = get_class($this);
		}

		$seznamTrid[] = $trida;//ulozime si prave zpracovavanou tridu do seznamu
		if (($rodic = get_parent_class($trida))) {//existuje rodicovska trida pro prave zpracovavanou tridu
			$seznamTrid = array_merge($seznamTrid,$this->getClassesStructure($rodic));//do seznamu trid pridame dalsi, rodicovske tridy pomoci rekurze - odRodicePoPotomka neni treba predavat, vyhodnocuje se jen pri finalnim seznamu
		}

		if(($trida === get_class($this)) and $odRodicePoPotomka) {//jsme-li na konci vseh hledani (rekurze uz jsou za nami), proverime pozadavek na serazeni vysledku
			$seznamTrid = array_reverse($seznamTrid);
		}

		return $seznamTrid;//vratime hotovy seznam trid
	}

	/**
	 *
	 * @param bool $allow
	 */
	public function setAllowMagicGetToMethod($allow) {
		$this->allowMagicGetToMethod = (bool)$allow;
	}

	/**
	 *
	 * @param bool $use
	 */
	public function setUseGetMethodToAccessProperty($use) {
		$this->useGetMethodToAccessProperty = (bool)$use;
	}

	public function setAllowNullAfterFilling($allow) {
		$this->allowNullAfterFilling = (bool)$allow;
	}

	/*public static jePodtridou($potomek, $rodic, $potomekSebeSama = false){

	}*/

	//vlastnosti, pres ktere uzavirame spojeni, musi byt nanejvis protected, ne-li public, jinak se k nim tato funkce nedostane(POZOR!! docasne parametry funkce se bez rucniho vlozeni jako parametr sem nedostanou), nebo dodane jako parametry
	//reaguje na podtridy PraceSql, nic jineho a vola jejich __destructor, ve kterem ocekavame volani odpojSql, ktere vyhleda kazdou tridu pripojeni a zavola jeji odpojSe
	protected function disconnectSqlClasses()
	{//POZOR!! pokud predas stylem Singleton promennou obsahujici odkaz na zive pripojeni, zruseni kterekoli ze zdvou trid zrusi pripojeni
		$prohledejPole = false;
		$rekurzivne = true;
		$vlastnosti = array();
		if(sizeof(func_get_args()) > 0){//dostali jsme nejake parametry, budeme hledat pripojeni jen v nich, ale zato budeme prohledavat i pole a to rekurzivne
			$vlastnosti = func_get_args();
			if($vlastnosti[0] === false){//pokud je prvni parametr false, povazujeme to za pozadavek na nerekurzivni hledani dcerinych trid PraceSql
				$rekurzivne = false;
				unset($vlastnosti[0]);//zrusime spotrebni parametr
			}
		}
		if(sizeof($vlastnosti) > 0){
			$prohledejPole = true;
		}else{
			$vlastnosti = get_object_vars($this);
		}
		foreach($vlastnosti as &$propertyName){//projdeme veskere vlastnosti tidy(teto a dcerinych)
			$this->disconnectSqlClass($propertyName,$prohledejPole,$rekurzivne);
		}
	}

	private function disconnectSqlClass($propertyName,$prohledejPole,$rekurzivne)
	{
		if($prohledejPole and is_array($propertyName)){//jestlize propertyName muze byt pole s dalsimi vlastnostmi a v nich tridy
			foreach($propertyName as &$podvlastnost){
				$this->{__FUNCTION__}($podvlastnost,$prohledejPole,$rekurzivne);//rekurze
			}
		}elseif(is_object($propertyName)){//nic jineho nez objekty nas nezajimaji
			if(is_subclass_of($propertyName,'PraceSql')){//jestlize jsou potomkem abstraktni vlastnosti PraceSql
				if(method_exists($propertyName,'__destruct')){//jestlize je ve tride definovana metoda __destruct
					$propertyName->__destruct();//zavolame destructor, ve kterem ocekavame postarani se o odpojeni
				}
			}
			if($rekurzivne){
				$this->disconnectSqlClasses($rekurzivne,get_object_vars($propertyName));
			}
		}
	}

	protected function makePropertyReadable($propertyName)
	{
		if (sizeof(func_get_args()) > 1) {
			trigger_error('Method ' . __METHOD__ . ' accepts only one parameter', E_USER_WARNING);
		}

		if (!is_bool($this->readable) && !in_array($propertyName, $this->readable))
			$this->readable[] = $propertyName;
	}

	protected function makePropertiesReadable(){
		foreach(func_get_args() as $propertyName){
			$this->makePropertyReadable($propertyName);
		}
	}

	protected function makeAllPropertiesReadable(){
		$this->readable = true;
	}

	protected function readableAll(){
		$this->makeAllPropertiesReadable();
	}

	protected function writableAll(){
		$this->writable = true;
	}

	/**
	* For reading protected properties, marked in readable list
	*
	* @var string name of property to access
	*
	* @return mixed property value
	*/
	public function __get($propertyName)//private properties of child classes can not be accessed, public properties are accesed directly, so only protected properties are managed by this function
	{
		if ($this->useGetMethodToAccessProperty &&
		  method_exists($this,'get'.ucfirst($propertyName))) { //get method has priority
			$getMethodName = 'get'.ucfirst($propertyName);

			return $this->$getMethodName();
		}

		if (($this->readable === true) or in_array($propertyName,$this->readable)) {//osetreni proti chybe uzivatele tridy

			return $this->getPropertyOfClass($propertyName);
		}

		//propertyName can not be accessed
		if (in_array($propertyName, $this->getListOfProperties())) {
			$reason = 'is not for reading';
		} else {
			$reason = 'does not exists';
		}

		throw new \RuntimeException("property '$propertyName' $reason (has been claimed by class ".get_class($this).")", E_USER_ERROR);
	}

	public function __call($methodName, $arguments) {
		if (preg_match('~^get[[:upper:]]~', $methodName)) {
			if ($this->allowMagicGetToMethod) {
				$propertyName = lcfirst(substr($methodName, 3));
				if (in_array($propertyName, $this->getListOfProperties())) {
					if (($this->readable === TRUE) ||
					  in_array($propertyName, $this->readable)) {

						return $this->getPropertyOfClass($propertyName);
					} else {
						$reason = 'Property of name ' . $propertyName . ' required by
							magicaly created method ' . $methodName . ' is not readable.';
					}
				} else {
					$reason = 'Property of name ' . $propertyName . ' required by
						magicaly created method ' . $methodName . ' does not exists.';
				}
			} else {
				$reason = 'Magic calling get methods for readable properties is
					not allowed.';
			}
		} else {
			$reason = 'Method of name ' . $methodName . ' is not allowed
				for magic call.';
		}

		throw new \RuntimeException($reason . ' Method has been claimed
			by class ' . get_class($this) . '.', E_USER_ERROR);
	}

	/**
	* @return Array of non-private properties of actual class
	*/
	protected function getListOfProperties()
	{
		return array_keys(get_class_vars(get_class($this)));
	}

	public function __set($propertyName,$value){
		if(($this->writable === true) or in_array($propertyName,$this->writable)){//osetreni proti chybe uzivatele tridy
			return $this->setProperty($propertyName,$value);
		}else{
			if (in_array($propertyName,$this->getListOfProperties())) {
				$reason = 'is not for writing';
			}else{
				$reason = 'does not exists or is private';
			}
			throw new \RuntimeException("property '$propertyName' $reason (for writing has been claimed by class ".get_class($this).")", E_USER_ERROR);
		}
	}

	public static function getSystemRealPathOfDir($dirPath)
	{
		if (!is_dir($dirPath)){//if given name is realy directory
			throw new \RuntimeException("Directory of name '$dirPath' was not found", E_USER_WARNING);
		}
		if (!is_readable($dirPath)) {
			throw new \RuntimeException("Directory '$dirPath' can not be readed", E_USER_WARNING);
		}
		$originalCwd = getcwd();
		if (!chdir($dirPath)) {//using directory as working place to get standarized name in next step
			throw new \RuntimeException("Directory of name '$dirPath' can not be entered", E_USER_WARNING);
		}
		$systemRealPath = getcwd();
		if (!chdir($originalCwd)) {//returning previus cwd back
			throw new \RuntimeException("Original working directory of name '$dirPath' could not be entered", E_USER_WARNING);
		}

		return $systemRealPath;
	}

	private function getPropertyOfClass($propertyName)
	{//POZOR!! pokud je nejaka propertyName jako NULL, pak ji tato funkce bere jako nenastavenou a stale dokola se snazi ji naplnit volanim prislusne funkce, ci dokonce tvrdi, ze neexistuje
		if(isset($this->$propertyName)){//pokud uz je propertyName urcena

			return $this->$propertyName;//vratime ji
		} elseif(method_exists($this,'set'.ucfirst($propertyName))) {//jinak zjistime, zda existuje metoda na jeji inicializaci
			call_user_func(array($this,'set'.ucfirst($propertyName)));//pokud ano, naplnime propertyName
			if (isset($this->$propertyName)) {//pokud tentokrat je propertyName nastavena

				return $this->$propertyName;//vratime ji
			}
		}

		if (array_key_exists($propertyName, get_class_vars(get_class($this)))) {
			if ($this->allowNullAfterFilling) {
				return $this->$propertyName; //NULL for sure
			} else {
				throw new \RuntimeException("Property '$propertyName' is NULL after filling", E_USER_ERROR);//funkce getPropertyOfClass nevratila hodnotu, vratime chybu
			}
		} else {
			throw new \RuntimeException("Property '$propertyName' is set as 'readable', but in class " . get_class($this) . " does not exists, or is private", E_USER_ERROR);//funkce getPropertyOfClass nevratila promennou, vratime chybu
		}
	}

	/**
	* Sets property of given name to given value
	*
	* @var propertyName , name of property to set
	* @var value , value to set
	*
	* @return void
	*/
	private function setProperty($propertyName,$value)
	{
		$methodName = 'set'.ucfirst($propertyName);
		if (method_exists($this,$methodName)) {
			$this->$methodName($value);
		} else {
			if (property_exists($this,$propertyName)) {//osetreni proti chybe tvurce tridy
				$this->$propertyName = $value;
			} else {
				throw new \RuntimeException("property '$propertyName' is set as 'writable', but does not exists, or is private", E_USER_ERROR);
			}
		}
	}
}