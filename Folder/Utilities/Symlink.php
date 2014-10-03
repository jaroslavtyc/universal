<?php
namespace universal;

class Symlink extends BaseClass {

	const NO_REWRITE = 0;
	const REWRITE_LINK = 1;//mode to rewrite any link, do not touch original files at all
	const REWRITE_FILE = 10;//mode to rewrite real file, that means delete! existing and create link instead of it, if conflict occurs
	const REWRITE_DIR = 100;//mode to rewrite real direcotry, that means delete! existing and create link instead of it, if conflict occurs

	public function __construct(){}

	/**
	* Reads CSV with pairs link-name and source full-path
	*
	* @param $pathToSymlinkListFile String full path to CSV file with laue pairs
	* @param $firstRowContainsHeading Bool token if first row is occupied by heading
	* @param $dataWrapper String character(s) surrounding data entity in CSV
	* @param $columnDelimiter String character(s) delimiting data entity in CSV
	*
	* @throw Exception if problem with accessing or reading file occurs or format of data is not correct
	* @return Array data from CSV with added records with key symlink_result containing Boolean token about success of creating link and key symlink_error_report containing String description of error
	*/
	public function makeFromFileList($pathToSymlinkListFile, $firstRowContainsHeading = FALSE, $dataWrapper = '"', $columnDelimiter = ',')
	{
		try {
			$csv = new Csv($pathToSymlinkListFile);//Csv class checks file accessibility
			//csv file is with heading in fact, but we want to manage heading later ourself
			$content = $csv->read(Csv::WITHOUT_HEADING, FALSE, FALSE, $dataWrapper, $columnDelimiter);
			if ($content) {
				if ($firstRowContainsHeading)
					unset($content[0]);//first row contained irrelevant heading
				foreach($content as &$row){
					if (empty($row[0]) || empty($row[1]))
						throw new Exception('Format of CSV with symlink information does not have proper format',E_USER_WARNING);
					try {//creating link should throws an exception
						$row['symlink_result'] = $this->make($row[0], $row[1]);
					} catch(Exception $e) {
						$row['symlink_result'] = FALSE;
						$row['symlink_error_report'] = $e->getMessage();//exception is transformed to whole text information
					}
				}

				return $content;
			}

			return FALSE;
		} catch(Exception $e) {
			throw new Exception('Symbolic link creation has been unexpectedly terminated with message ' . $e->getMessage(), E_USER_WARNING);
		}
	}

	/**
	* Creates link of desired name to given source folder in desired directory
	*
	* @param $sourceFullPath String path to file or directory to link to
	* @param $linkFullPath String full name of link to create
	* @param $allowRewrite Int type of existing folders to rewrite by new link
	*
	* @return Boolean token about result
	*/
	public function make($sourceFullPath, $linkFullPath, $allowRewrite = self::NO_REWRITE)
	{
		$source = new Folder($sourceFullPath);
		if (file_exists($linkFullPath)) {
			$target = new Folder($linkFullPath);
			if (!$allowRewrite && !($this->getFolderTypeCode($target->folderType) & $allowRewrite))
				throw new Exception('Same type of folder exists on target location', E_USER_WARNING);
			else {
				if (is_dir($linkFullPath)) {
					\universal\FolderUtilities\FolderUtilities::unlinkDir($linkFullPath, TRUE);
				} else
					unlink($linkFullPath);
			}
		}
		$result = symlink($source->fullName, $linkFullPath);

		return $result;
	}

	protected function getFolderTypeCode($folderType)
	{
		switch($folderType){
			case Folder::TYPE_LINK :
				return self::REWRITE_LINK;
			case Folder::TYPE_FILE :
				return self::REWRITE_FILE;
			case Folder::TYPE_DIR :
				return self::REWRITE_DIR;
			default :
				return selff::NO_REWRITE;
		}
	}
}