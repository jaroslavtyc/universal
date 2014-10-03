<?php
namespace universal\Folder;

class Folder extends \universal\BaseClass {

	const ORIGINAL_NAME = 0;
	const TYPE_FIFO = 'fifo'; //named pipe
	const TYPE_CHAR = 'char';
	const TYPE_DIR = 'dir';
	const TYPE_BLOCK = 'block';
	const TYPE_LINK = 'link';
	const TYPE_FILE = 'file';
	const TYPE_SOCKET = 'socket';
	const TYPE_UNKNOWN = 'unknown';

	/**
	* Type of folder (fifo, char, dir, block, link, file, socket, unknown)
	*
	* @string
	*/
	protected $folderType;

	/**
	* Full dir path to folder, if folder is directory, so without itself
	*
	* @var string
	*/
	protected $dir;

	/**
	* Name of file, if folder is file, then with suffix
	*
	* @var string
	*/
	protected $name;

	/**
	* Alias of name
	*
	* @var reference
	*/
	protected $basename;

	/**
	* Full name of folder, including path
	*
	* @var string
	*/
	protected $fullName;

	/**
	* @var string full path to file
	*/
	public function __construct($fullName)
	{
		$this->readableAll();
		$this->setFullName($fullName);
	}

	/**
	* If magic get was called, initialization by load() was probably not preformed yet.
	* Overloaded __get will call load() than.
	*
	* Used for late binding
	*
	* @var name of property to get
	*
	* @return mixed property value
	*/
	public function __get($propertyName)
	{
		if (property_exists($this, $propertyName) && !method_exists($this,'get'.ucfirst($propertyName)) && !isset($this->$propertyName)) //property is not set yet; get method has priority
		{
			if ($this->load()) { //load of folder informations was succesfull
				return parent::__get($propertyName);
			} else {
				if (!isset($this->fullName) || !$this->fullName) {
					$detail = 'Does not have full folder name';
				} else {
					$detail = 'Folder is unaccessible, check if exists and if is readable.';
				}
				throw new \RuntimeException('Can not load folder information.' . $detail, E_USER_WARNING);
			}
		} else {
			return parent::__get($propertyName);
		}
	}

	/**
	* Geter for fullName
	*
	* @return string full name of file
	*/
	public function getFullName()
	{
		if (!isset($this->fullName))
			throw new Exception('Full name is not set');

		return $this->fullName;
	}

	/**
	* Copy folder to another location
	*
	* @param $destinationDir String full path to dir of new location
	* @param $newFoldername String name of folder on destination
	* @param $rewriteExisting Bool if rewrite folder with same name is allowed
	* @return Bool token about success
	*/
	public function copyTo($destinationDir, $newFoldername = self::ORIGINAL_NAME, $rewriteExisting = false)
	{
		return $this->transmision($destinationDir, $newFoldername, $rewriteExisting, 'copy');
	}

	/**
	* Move folder to another location
	*
	* @param $destinationDir String full path to dir of new location
	* @param $newFoldername String name of folder on destination
	* @param $rewriteExisting Bool if rewrite folder with same name is allowed
	* @return Bool token about success
	*/
	public function moveTo($destinationDir, $newFoldername = self::ORIGINAL_NAME, $rewriteExisting = false)
	{
		return $this->transmision($destinationDir, $newFoldername, $rewriteExisting, 'move');
	}

	/**
	* Checks if on given path is folder
	*
	* @return bool token about folder existence
	*/
	public function folderExists()
	{
		if (!isset($this->fullName)) {
			throw new \RuntimeException('Name of folder is not set, can not determine its existence',E_USER_WARNING);
		}
		return file_exists($this->fullName);//inner PHP function checks existence of folder (file or directory)
	}

	//----INNER FUNCTIONS-----

	/**
	* If file exists, load file information and fulfill by them proper properties
	*
	* @return bool token about process result
	*/
	protected function load()
	{
		if ($this->folderExists()) {
			$info = pathinfo($this->fullName);
			$this->folderType = filetype($this->fullName);
			$this->dir = $info['dirname'];
			$this->name = $info['basename'];
			$this->basename = &$this->name;

			return $info;
		} else
			return FALSE;
	}

	/**
	* Call given function name with parameters of original fullname and new fullname of folder
	*
	* @param $destinationDir String full path to dir of new location
	* @param $newFoldername String name of folder on destination
	* @param $rewriteExisting Bool if rewrite folder with same name is allowed
	* @return Bool token about success
	*/
	protected function transmision($destinationDir, $newFoldername, $rewriteExisting, $functionToUse)
	{
		$destinationDir = \universal\FolderUtilities\FolderUtilities::makeStandarizedDirpath($destinationDir);
		if ($newFoldername === self::ORIGINAL_NAME) {
			$newFoldername = $this->name;
		}
		if (!is_writable($destinationDir)) {
			return FALSE;
		} else {
			if (!$rewriteExisting && !file_exists($destinationDir . $newFoldername)) {
				return FALSE;
			} else {
				return call_user_func($functionToUse, $this->fullName, $destinationDir . $newFoldername);
			}
		}
	}

	/**
	* Wrapper for inner PHP funcion filetype
	*
	* @return string about file type (fifo, char, dir, block, link, file, socket, unknown)
	*/
	protected function getFileType()
	{
		if (!isset($this->folderType)) {
			if ($this->folderExists())
			$this->folderType = filetype($this->fullName);
		}

		return $this->folderType;
	}

	/**
	* Seter for full name of folder - it is not renamer
	*
	* @return void
	*/
	protected function setFullName($fullName)
	{
		$fullName = (string)$fullName;
		if (file_exists($fullName)) {
			$this->fullName = $fullName;
		} else {
			throw new \RuntimeException('Folder ' . $fullName . ' is unaccessible', E_USER_WARNING);
		}
	}
}
