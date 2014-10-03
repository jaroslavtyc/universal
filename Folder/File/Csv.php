<?php
namespace universal\Folder\File;

class Csv extends File {

	const WITHOUT_HEADING = 0;
	const WITH_ORIGINAL_HEADING = 1;
	const WITH_MACHINE_HEADING = 2;

	protected $sourceEncoding;
	protected $resultEncoding;

	public function __construct($fullName, $sourceEncoding = 'utf-8', $resultEncoding = 'UTF-8')
	{
		parent::__construct($fullName);
		$this->sourceEncoding = $sourceEncoding;
		$this->resultEncoding = $resultEncoding;
	}

	/**
	* @return Array rows of readed CSV file
	*/
	public function read($pathToCsv, $typeOfHeading = self::WITHOUT_HEADING, $writeThrough = false, $voidReplaceByZero = false, $dataWrapper = '"', $columnDelimiter = ','){
		$readedRows = array();
		$csv = fopen($this->fullName,'r');
		if($typeOfHeading != self::WITHOUT_HEADING){//pokud vubec zahlavi chceme
			if($columnsNames = fgetcsv($csv,'"',',')){//nacteme prvni row jako zahlavi
				foreach($columnsNames as $index=>$columnName){//nactu prvni row jako nazvy sloupcu
					if ($this->sourceEncoding != $this->resultEncoding) {
						iconv($this->sourceEncoding, $this->resultEncoding, $columnName);
					}
					switch($typeOfHeading){
						case self::WITH_ORIGINAL_HEADING :
							$columnsNames[$index] = $columnName;
							break;
						case self::WITH_MACHINE_HEADING :
							$columnsNames[$index] = FormatUtilities::udelejNazevPromenne($columnName);
							break;
						default:
							trigger_error('Unknown request for heading format ('.(string)$typeOfHeading.'). Will be used original text from file',E_USER_NOTICE);
							$columnsNames[$index] = $columnName;
					}
				}
			}
		}
		while($row = fgetcsv($csv, $dataWrapper, $columnDelimiter)){//dokud je ze souboru co cist
			if(isset($columnsNames)){
				$row = array_combine($columnsNames, $row); //nazvy sloupcu pouziji jako klice
			}
			if($writeThrough){//jestlize chceme na misto prazdnych udaju doplnit hodnoty stejneho sloupce predchoziho radku
				foreach($row as $index=>$cell){
					if($cell === ''){//pokud je prvek radku prazdny
						if(isset($readedRows[sizeof($readedRows)-1][$index])){//pokud existoval tento prvek v predchozim radku
							$row[$index] = $readedRows[sizeof($readedRows)-1][$index];//nahradime soucasne prazdno predchozim prvkem
						}
					} elseif ($this->sourceEncoding != $this->resultEncoding) {
						iconv($this->sourceEncoding, $this->resultEncoding, $row);
					}
				}
			}
			//voidReplaceByZero musi byt az po writeThrough, v opacnem pripade bychom uz nemeli duvod (podminku) k propisovani
			if($voidReplaceByZero){//jestlize chceme na misto prazdnych udaju doplnit nuly
				foreach($row as $index=>$cell){
					if($cell === ''){//pokud je prvek radku prazdny
						$row[$index] = 0;//nahradime soucasny prazdny retezec nulou
					}
				}
			}
			$readedRows[] = $row;
		}

		return $readedRows;
	}

	public static function readWithHeading($pathToCsv, $machineNamesOfHeading = false){
		return self::read($pathToCsv, ($machineNamesOfHeading ? 2 : 1));
	}

	public static function readWithHeadingWriteThrough($pathToCsv, $machineNamesOfHeading = false){
		return self::read($pathToCsv, ($machineNamesOfHeading ? 2 : 1), true);
	}

	public static function readWithHeadingWriteThroughReplaceVoidByZero($pathToCsv, $machineNamesOfHeading = false){
		return self::read($pathToCsv, ($machineNamesOfHeading ? 2 : 1), true, true);
	}
}
