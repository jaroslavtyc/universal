<?php
namespace universal;

abstract class Object {

	private $listOfReadableProperties = array();
	private $writable = array();
	private $allowedMagicGetToMethod = TRUE;
	private $allowedGetMethodToAccessProperty = TRUE;
	private $allowNullAfterFilling = FALSE;

	// ____________________________
	// ---- PROPERTIES ACCESS -----

	/**
	* For reading protected properties, involved in readable list
	*
	* @param string name of property to access
	*
	* @return mixed property value
	*/
	public function __get($propertyName)
	{
		if ($this->allowedGetMethodToAccessProperty &&
		 method_exists($this,'get'.ucfirst($propertyName))) {
			//get method has priority
			$getterMethodName = 'get'.ucfirst($propertyName);

			return $this->$getterMethodName();
		}

		if (in_array($propertyName,$this->listOfReadableProperties)) {

			return $this->getPropertyOfClass($propertyName);
		}

		//propertyName can not be accessed
		throw new \RuntimeException("property '$propertyName' is not visible from actual scope (has been claimed by class " . get_class($this) . ')', E_USER_ERROR);
	}

	/**
	 * Add given property names into list of properties allowed to read by __get
	 *
	 * @param string|array any number of strings or array with strings
	 * @return void
	 */
	protected function makePropertiesReadable() {
		foreach(func_get_args() as $propertyName){
			if (!is_string($propertyName)) {
				if (is_array($propertyName)) {
					foreach ($propertyName as $arrayItem) {
						$methodName = __METHOD__;
						$this->$methodName($arrayItem);
					}
				} else {
					trigger_error('Property name is not a string', E_USER_ERROR);
					continue;
				}
			}

			if (in_array($propertyName, $this->listOfReadableProperties)) {
				trigger_error(
					'Property ' . $propertyName . ' is already set as readale',
					E_USER_NOTICE
				);
			}

			if (!property_exists($this, $propertyName)) {
				trigger_error(
					'Property ' . $propertyName . ' is not visible from ' . get_class($this) . ' scope',
					E_USER_ERROR
				);

				continue;
			}

			$this->listOfReadableProperties[] = $propertyName;
		}
	}

	/**
	 * Sets every protected property as readable
	 */
	protected function setReadableAllProperties() {
		$this->makePropertiesReadable($this->getProtectedProperties());
	}

	/**
	 * Sets every protected property as writtable - not recommended
	 * (this should be used with combination of magic __get and bounded
	 * functionality on it)
	 */
	protected function setWritableAllProperties() {
		$this->makePropertiesWritable($this->getProtectedProperties());
	}

	// ________________________
	// ---- METHODS ACCESS ----

	/**
	 *
	 * @param bool $allow
	 */
	public function setAllowMagicGetToMethod($allow) {
		$this->allowedMagicGetToMethod = (bool)$allow;
	}

	/**
	 *
	 * @param bool $use
	 */
	public function setAllowedGetMethodToAccessProperty($use) {
		$this->allowedGetMethodToAccessProperty = (bool)$use;
	}

	/**
	 *
	 * @param bool $allow
	 */
	public function setAllowNullAfterFilling($allow) {
		$this->allowNullAfterFilling = (bool)$allow;
	}

	public function __call($methodName, $arguments) {
		if (preg_match('~^get[[:upper:]]~', $methodName)) {
			if ($this->allowedMagicGetToMethod) {
				$propertyName = lcfirst(substr($methodName, 3));
				if (in_array($propertyName, $this->getListOfProperties())) {
					if (($this->listOfReadableProperties === TRUE) ||
					  in_array($propertyName, $this->listOfReadableProperties)) {

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

	// ______________________
	// ------ INNER FACILITIES -------

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

	/**
	 * Finds out protected properties of this object
	 *
	 * @return array list of protected properties
	 */
	private function getProtectedProperties() {
		$publicProperties = ObjectUtilities::getObjectPublicProperties($this);
		$publicAndProtectedProperties = get_object_vars($this);

		return array_diff($publicAndProtectedProperties, $publicProperties);
	}
}