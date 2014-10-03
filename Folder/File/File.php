<?php
namespace universal\Folder\File;

class File extends \universal\Folder\Folder {

	const DEFAULT_NEWLINE_CHARACTER = "\n"; //unix newline character

	/**
	* Name of file without suffix
	*
	* @string
	*/
	protected $filename;

	/**
	* Suffix of file
	*
	* @string
	*/
	protected $suffix;

	/**
	* Size of file in bytes
	*
	* @int
	*/
	protected $size;

	/**
	* Content of file
	*
	* @string
	*/
	protected $content;

	/**
	* @var string full path to file
	*/
	public function __construct($fullName)
	{
		parent::__construct($fullName);
		if ($this->getFileType() != self::TYPE_FILE)
			throw new Exception('Folder is not type of file, but ' . $this->fileType);
	}

	/**
	* Formats size of file according to required size unit
	*
	* @param $sizeUnit Int representing size unit according to constants of this class
	* @param $dynamicSizeFormat Bool if required re-aplying formating size by size unit until range of formated size is greater then choosen size unit, adequately changing unit multiple
	* @return String humanly formated size
	*/
	public function getFormatedSize($sizeUnit = \universal\Folder\Utilities\FileUtilities::SIZE_UNIT_KB, $dynamicSizeFormat = FALSE, $absolutePrecision = FALSE)
	{
		if (!isset($this->size))
			throw new RuntimeExceptio('Size of file is not known', E_USER_WARNING);
		$formatedSize = \universal\Folder\Utilities\FileUtilities::makeFormatedSize(
			$this->size, $sizeUnit, $dynamicSizeFormat, $absolutePrecision);

		return implode(' ', $formatedSize);
	}

	/**
	* Checks if on given path is folder and if it is file
	*
	* @return bool token about file existence
	*/
	public function fileExists()
	{
		try {
			return (parent::folderExists() && is_file($this->fullName));
		} catch (Exception $e) {
			throw new \RuntimeException('Name of file is not set, can not determine its existence',E_USER_WARNING);
		}
	}

	//----INNER FUNCTIONS-----

	/**
	* If file exists, load file information and fulfill by them proper properties
	*
	* @return Mixed, Array pathinfo or Bool FALSE if error occurs
	*/
	protected function load()
	{
		if (!$this->fileExists())
			return FALSE;
		else {
			$info = parent::load();
			$this->suffix = isset($info['extension'])
				? $info['extension']
				: FALSE;
			$this->filename = $info['filename'];
			$this->size = filesize($this->fullName);

			return $info;
		}
	}

	/**
	* Call given function name with parameters of original fullname and new fullname of file
	*
	* @param $destinationDir String full path to dir of new location
	* @param $newFilename String name of file on destination
	* @param $rewriteExisting Bool if rewrite file with same name is allowed
	* @return Bool token about success
	*/
	protected function transmision($destinationDir, $newFilename, $rewriteExisting, $functionToUse)
	{
		$destinationDir = \universal\FolderUtilities\FolderUtilities::makeStandarizedDirpath($destinationDir);
		if ($newFilename === self::ORIGINAL_NAME) {
			$newFilename = $this->name;
		}
		if (!is_writable($destinationDir)) {
			return FALSE;
		} else {
			if (!$rewriteExisting && !file_exists($destinationDir . $newFilename)) {
				return FALSE;
			} else {
				return call_user_func($functionToUse, $this->fullName, $destinationDir . $newFilename);
			}
		}
	}

	/**
	* Seter for full name of file
	*
	* @return void
	*/
	protected function setFullName($fullName)
	{
		$fullName = (string)$fullName;
		if (is_file($fullName)) {
			parent::setFullName($fullName);
		} else {
			throw new \RuntimeException('Folder ' . $fullName . ' is not accessible', E_USER_WARNING);
		}
	}

	/**
	* Reads content of file and returns it
	*
	* @param bool forcing reading content from file
	* @return String content of file
	*/
	public function getContent($reRead = FALSE) {//function is protected to force calling __get method and therefore use control of this __get
		if ($reRead) {
			//returning content imediately without saving into content property
			// to allow lower memory requirements
			return file_get_contents($this->fullName);
		}

		if (!isset($this->content)) {
			$this->content = file_get_contents($this->fullName);
		}

		return $this->content;
	}

	/**
	 * Getter for content splited to array by rows
	 *
	 * @param bool forcing reading content from file
	 * @param callback string with valid callback function on array with valid
	 * object and method
	 *
	 * @return array content of file splited by rows
	 */
	public function getContentArray($reRead = FALSE, $rowCallback = FALSE) {
		if ($reRead || !isset($this->content)) {
			$resource = fopen($this->fullName, 'r');
			$content = array();
			while (!feof($resource)) {
				$row = fgets($resource);
				if ($rowCallback) {
					$row = call_user_func($rowCallback, $row);
				}
				$content[] = $row;
			}

			return $content;
		}

		return preg_split('~\r\n|[\r\n]~', $this->content);
	}
}
