<?php
namespace universal;

require_once(__DIR__ . '/../initialization/basic_functions.php');
require_once(__DIR__ . '/../initialization/exception_catching.php');
// ROOT_DIRECTORY, DOCUMENT_ROOT
require_once(__DIR__ . '/../initialization/basic_constants.php');
require_once(__DIR__ . '/Object.php');
require_once(__DIR__ . '/../Folder/Utilities/FolderUtilities.php');

define('AUTOLOAD_REQUIRE_ONCE_DIRECTORY',
	str_replace('\\','/',
		strrpos(getcwd(),DIRECTORY_SEPARATOR) !== 0
			? getcwd() . DIRECTORY_SEPARATOR
			: getcwd()
	)
);
/**
* Singleton Autoload class for automaticaly loading files with php code
* Searched script files have to be same name as required interfaces and classes (autoload is case sensitive!)
*/
class Autoload extends Object {

	private $listOfDirsWithScripts;
	private $dirsToAutoloadFrom;

	private $errorCatching;
	private $warningCatching;
	private $noticeCatching;
	private $noteCatching;

	private static $autoLoad;

	/**
	* For getting singleton Autoload instance - creates instance fo Autoload, if not in static property yet; returns that instance
	*
	* @return Object instance of this class
	*/
	public static function get()
	{
		if(!isset(self::$autoLoad)){
			self::$autoLoad = new self();
		}

		return self::$autoLoad;
	}

	/**
	 * Defines constant AUTOLOAD_CALLING_DIRECTORY holding directory of script, which called autoloading first; sets error handlers, standard directories to scan when autoload is needed
	 *
	 * @return void
	 */
	private function __construct()
	{
		define('AUTOLOAD_CALLING_DIRECTORY', self::getcwd());
		define('UNIVERSAL_ROOT_DIRECTORY', dirname(__FILE__).'/../');
		$this->setErrorManaging();
		$this->initializeDirsAndScriptsToDirs();
	}

	/**
	* Returns current working direcotry with trailing directory separator
	*
	* @return string
	*/
	public static function getCwd()
	{
		$cwd = getcwd();
		if (strrpos($cwd,DIRECTORY_SEPARATOR) !== 0) {
			$cwd .= DIRECTORY_SEPARATOR;
		}

		return $cwd;
	}

	/**
	* Sets predefined exception catching managers for E_USER error levels
	*/
	private function setErrorManaging()
	{
		$this->setExceptionCatching();
		$this->setErrorHandlers();
	}

	private function setExceptionCatching()
	{
		$this->errorCatching = new ErrorCatching();
		$this->warningCatching = new WarningCatching();
		$this->noticeCatching = new NoticeCatching();
		$this->noteCatching = new NoteCatching();
	}

	private function setErrorHandlers()
	{
		set_error_handler(array($this,'catchException'),E_USER_NOTICE);
		set_error_handler(array($this,'catchException'),E_USER_WARNING);
		set_error_handler(array($this,'catchException'),E_USER_ERROR);
	}

	public function __desctruct()
	{
		restore_error_handler();//important for releasing instance of this class, managing error
	}

	public static function extendRange()
	{
		$autoload = self::get();
		foreach(ArrayUtilities::trimArray(func_get_args()) as $folder){
			$autoload->addDirToAutoloadFrom($folder);
		}
	}

	public static function includePath($folder)
	{
		$autoload = self::get();
		$autoload->addDirToAutoloadFrom($folder);
	}

	private function initializeScriptsToDirs()
	{
		if (!is_null($this->listOfDirsWithScripts)) {
			trigger_error('List of dirs with scripts is already initialized', E_USER_NOTICE);
		}
		$this->listOfDirsWithScripts = array();
	}

	public function catchException($errorCode, $errorMessage, $nameOfScriptWithError, $rowWithError){//error handler is setted up to give errors to this function, which uses proper class to manage it in dependency of error level
		switch($errorCode){
			case E_USER_NOTICE:
				$this->noteCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			case E_USER_WARNING:
				$this->warningCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			case E_USER_ERROR:
				$this->errorCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			default:
				$this->noticeCatching->catchOnce("(script error(code $errorCode))\t".$errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
		}
	}

	private function initializeDirsAndScriptsToDirs(){
		$this->initializeScriptsToDirs();
		$this->dirsToAutoloadFrom = array();
		if (isset($_SERVER['DOCUMENT_ROOT'])) { //script is called as web page
			$previousCwd = getcwd();
			chdir(UNIVERSAL_ROOT_DIRECTORY . '/../'); //for getting full path to autoload wraping dir
			if (str_replace('\\','/',AUTOLOAD_CALLING_DIRECTORY) != str_replace('\\','/',getcwd())) { //directory from where was autoload called first is not same as autoload wraping dir (otherwise scan of every project can occurs)
				$this->addDirToAutoloadFrom(AUTOLOAD_CALLING_DIRECTORY); //involve directory from which autoload scripts was called into list of directories to scan for php files
			}
			chdir($previousCwd);
		}
		$this->addDirToAutoloadFrom(UNIVERSAL_ROOT_DIRECTORY); //homedir of autoload itself
	}

	/**
	 * Checks if given dirpath is realy directory and if can be readed; if is not in list of dirs, is putted into, all scripts of it are loaded to list for later use
	 *
	 * @throw Exception if fail of reading direcotory occurs
	 * @param String $dirPath full path to directory to recursively scan for scripts
	 * @return void
	 */
	private function addDirToAutoloadFrom($dirPath){
		$dirPath = self::getSystemRealPathOfDir($dirPath);
		if (!in_array($dirPath, $this->dirsToAutoloadFrom)) {//if this dir is not in list yet
			$this->dirsToAutoloadFrom[] = $dirPath;//save new directory to list
			$this->setScriptsToDir($dirPath);//set scripts of this folder to list of scripts for autoloading
		}
	}

	/**
	* Returns list of scripts of given directory, not recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan for scripts
	* @return Array list of founded script files, without suffix .php
	*/
	private function getScriptsInDirectory($dirPath)
	{
		$scripts = array();
		foreach(scandir($dirPath) as $folder){
			if (is_file($dirPath . $folder) and (strstr($folder,'.') == '.php')) {
				$scripts[] = substr($folder,0,strrpos($folder,'.'));
			}
		}

		return $scripts;
	}

	/**
	* Set scripts of given directory to list of scripts, indexed by holding directory name, recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan for scripts
	* @return Int number of founded scripts
	*/
	private function setScriptsToDir($dirPath)
	{
		$dirPath = $dirPath . DIRECTORY_SEPARATOR;
		$scriptsInDir = $this->getScriptsInDirectory($dirPath);
		$count = sizeof($scriptsInDir);
		if ($count) {
			$this->listOfDirsWithScripts[$dirPath] = $scriptsInDir;
		}
		foreach($this->getSubdirectories($dirPath) as $subdir){
			$count += $this->setScriptsToDir($subdir);
		}

		return $count;
	}

		/**
	* Returns list of subdirectories of given directory, not recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan subdirectories
	* @return Array list of founded subdirectories
	*/
	private function getSubdirectories($dirPath)
	{
		$subdirs = array();
		foreach(scandir($dirPath) as $folder){
			if (is_dir($dirPath . $folder) and $folder != '.' and $folder != '..'){
				$subdirs[] = $dirPath . $folder;
			}
		}

		return $subdirs;
	}

	/**
	* Searches for script file in list of scripts from scanned areas and requires scripts file if exists in list
	*
	* @param String $script name of script to load, without suffix - basicaly name of class, case sensitive
	* @return Mixed bool token about success or void when searching was unsuccessfull, but more autoloaders are registered and not used yet
	*/
	public static function that($script)
	{
		$autoload = self::get();
		if (self::isNamespaced($script)) {
			$scriptWithDoubledBackslashes = str_replace('\\', '\\\\', $script);
			foreach($autoload->listOfDirsWithScripts as $folderWithScripts => $scriptsInDir){
				foreach($scriptsInDir as $scriptInDir) {
					$namespacedScriptInDir = str_replace('/', '\\', $folderWithScripts . $scriptInDir);
					if (preg_match('~\\\\' . $scriptWithDoubledBackslashes . '$~i', $namespacedScriptInDir)) {
						require($folderWithScripts . $scriptInDir . '.php');
						return TRUE;
					}
				}
			}
		} else {
			foreach($autoload->listOfDirsWithScripts as $folderWithScripts => $scriptsInDir){
				if (in_array($script, $scriptsInDir)){
					require($folderWithScripts . $script . '.php');

					return TRUE;
				}
			}
			$autoloadFunctions = spl_autoload_functions();//get all registered autoload functions
			foreach($autoloadFunctions as $tier=>$autoloadFunction) {//passing throught list of registered autoloaders, tier reflects sequence of registering autoloaders, include prepending
				if (($autoloadFunction[0] == __CLASS__) && ($autoloadFunction[1] == __FUNCTION__)) {//found this function
					if ($tier < (sizeof($autoloadFunctions) -1)) {//and this is not last registered autoloader

						return;//end without exception to let next autoloader to do his work
					}
				}
			}
		}

		trigger_error("Script '$script.php' was not found in folder
			(and its eventual subfolders) " .
			((sizeof($autoload->dirsToAutoloadFrom) > 1) ? 's' : '') . ' ' .
			implode(',',$autoload->dirsToAutoloadFrom) . ')', E_USER_ERROR);

		return FALSE;
	}

	protected static function isNamespaced($name) {
		return preg_match('~\\\\~', $name);
	}

	protected static function removeNamespacesFromClassName($className) {
		$result = array();
		preg_match('~(?P<withoutNamespaces>[^\\\\]+)$~', $className, $result);
		return $result['withoutNamespaces'];
	}
}