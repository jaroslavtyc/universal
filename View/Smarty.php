<?php
namespace universal\View;

require_once(__DIR__.'/../libraries/smarty/Smarty.class.php'); //do not match to Tyc autoloader convention

class Smarty extends \Smarty {

	/**
	* Holder of singleton of this class
	*
	* @var Object of class Smarty
	*/
	private static $smartyInstance;

	/**
	* For getting singleton Smarty instance - creates instance fo Smarty, if not in static property yet; returns that instance
	*
	* @return Smarty
	*/
	public static function get($recompileAll = FALSE) {
		if(!isset(self::$smartyInstance)){
			self::$smartyInstance = new self();
		}
		if ($recompileAll) {
			self::$smartyInstance->compileAllTemplates('.tpl', TRUE);
		}

		return self::$smartyInstance;
	}

	public function __construct() {
		parent::__construct();
		//at first is for templates searched in project folder, in second step in universal templates
		$this->addTemplateDir(UNIVERSAL_ROOT_DIRECTORY . 'templates/');
		//universal extensions
		$this->addPluginsDir(UNIVERSAL_ROOT_DIRECTORY . 'extensions/smarty/plugins');
		//optional project specific extensions in project folder
		$this->addPluginsDir('.' . DS . 'extensions' . DS . 'smarty' . DS . 'plugins');
		//configuration can be set on project level via config in project folder
		$this->addConfigDir('.' . DS . 'configs' . DS . 'smarty' . DS);
		//if setting is not found in prohject folder, universal fodler is searched for config
		$this->addConfigDir(UNIVERSAL_ROOT_DIRECTORY . 'configs' . DS . 'smarty' . DS);
	}
}