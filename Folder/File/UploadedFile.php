<?php
namespace universal\Folder\File;

class UploadedFile extends File {

	const TEMP_NAME = 1;

	/**
	* Name of input file field and index name of uploaded file in _FILES variable
	*
	* @string
	*/
	protected $inputName;

	/**
	* Name of uploaded file
	*
	* @string
	*/
	protected $uploadedName;

	/**
	* MIME type of uploaded file
	*
	* @string
	*/
	protected $type;

	/**
	* Full name of uploaded file in filesystem
	*
	* @string
	*/
	protected $tmpName;

	/**
	* Directory containing temporary file
	*
	* @string
	*/
	protected $tmpDir;

	/**
	* Suffix of temporary file
	*
	* @string
	*/
	protected $tmpSuffix;

	/**
	* Name of temporary file without suffix
	*
	* @string
	*/
	protected $tmpFilename;

	/**
	* Code number of upload error
	*
	* @int
	*/
	protected $error;

	/**
	* Size of uploaded file in bytes
	*
	* @int
	*/
	protected $size;

	/**
	* @var string name of input type file
	*/
	public function __construct($inputName) {
		$this->setInputName($inputName);
		$this->load();
	}

	/**
	 *
	 * @param string $destinationDir directory to copy into
	 * @param string $name final name of file copy
	 * @param bool $rewriteExisting rewrite file with same name in destination dir
	 * @return bool token about success
	 */
	public function copyTo($destinationDir, $name = self::ORIGINAL_NAME, $rewriteExisting = FALSE) {
		//final name of file should be different
		switch ($name) {
			case self::TEMP_NAME :
				$name = $this->tmpName;
			case self::ORIGINAL_NAME :
				$name = $this->name;
			default:
				//name without change
		}

		$destinationDir = \universal\FolderUtilities\FolderUtilities::makeStandarizedDirpath($destinationDir);
		//first step is moving temporary file to destination folder
		if (!move_uploaded_file($this->tmpName , $destinationDir . $name)) {
			return FALSE;
		} else {
			//second step is copyig file back to original directory
			return copy($destinationDir . $name, $this->tmpName);
		}
	}

	public function moveTo($destinationDir, $name = self::ORIGINAL_NAME, $rewriteExisting = FALSE) {
		switch ($name) {
			case self::TEMP_NAME :
				$name = $this->tmpName;
			case self::ORIGINAL_NAME :
				$name = $this->name;
			default:
				//name without change
		}

		$destinationDir = \universal\FolderUtilities\FolderUtilities::makeStandarizedDirpath($destinationDir);

		if (!move_uploaded_file($this->tmpName , $destinationDir . $name)) {

			return FALSE;
		}

		//"conversion" to File class
		$this->tmpDir = NULL;
		$this->tmpFilename = NULL;
		$this->tmpName = NULL;
		$this->tmpSuffix = NULL;
		parent::__construct($destinationDir . $name);
		parent::load();

		return TRUE;
	}

	/**
	* Because of late binding function of __get we have to set geter for properties not intetned to be accesses only after calling load function
	*
	* @return string input name of uploading file
	*/
	public function getInputName() {
		if (!isset($this->inputName)) {
			trigger_error('Name of file from input form field is not set', E_USER_WARNING);
		} elseif (!$this->inputName) {
			trigger_error('Name of file from input form field is empty', E_USER_WARNING);
		} else {
			return $this->inputName;
		}
	}

	/**
	* Because of late binding function of __get we have to overload parent __get by this
	*
	* @return mixed value of property
	*/
	public function __get($propertyName) {
		if (method_exists($this,'get'.ucfirst($propertyName))) { //get method has priority
			$getMethodName = 'get'.ucfirst($propertyName);

			return $this->$getMethodName();
		} else {
			return parent::__get($propertyName);
		}
	}

	/**
	* Check if file was already uploaded
	*
	* @return bool token about file upload
	*/
	public function isUploaded() {
		return (
			isset($this->inputName) && $this->inputName
			&& isset($_FILES[$this->inputName])	&& $_FILES[$this->inputName]
			&& isset($_FILES[$this->inputName]['tmp_name']) && $_FILES[$this->inputName]['tmp_name'] //uploaded file got assigned temp name
		);
	}

	/**
	* Overloaded parent geter for fullName to add additional information when fullName is not set yet
	*
	* @return string full name of file
	*/
	public function getFullName() {
		if (!isset($this->fullName)) {
			throw new Exception('Full name of file is not know yet, needs upload file first',E_USER_WARNING);
		} else {
			return $this->fullName;
		}
	}

	//--------- INNER FUCNTIONS ----------------

	/**
	* Load informations about uploaded file
	*
	* Overloaded File::load() function
	*
	* @return bool token about file upload
	*/
	protected function load() {
		if (!$this->isUploaded()) {
			return FALSE;
		} else {
			$this->type = $_FILES[$this->inputName]['type'];
			$this->tmpName = $_FILES[$this->inputName]['tmp_name'];
			$this->error = $_FILES[$this->inputName]['error'];
			parent::__construct($this->tmpName);
			parent::load();
			$this->name = $_FILES[$this->inputName]['name'];
			$info = pathinfo($this->name);
			$this->tmpDir = $this->dir;
			$this->tmpSuffix = $this->suffix;
			$this->suffix = $info['extension'];
			$this->tmpFilename = $this->filename;
			$this->filename = $info['filename'];

			return $info;
		}
	}

	/**
	* @var string name of input type file
	*
	* @return bool token about process result
	*/
	protected function setInputName($inputName) {
		$inputName = (string)$inputName;
		if (strlen($inputName)) {
			$this->inputName = $inputName;

			return TRUE;
		} else {
			trigger_error('Name of file from input form field is empty', E_USER_WARNING);
		}

		return FALSE;
	}
}