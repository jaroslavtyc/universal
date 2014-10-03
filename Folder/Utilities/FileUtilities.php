<?php
namespace universal\Folder\Utilities;

class FileUtilities {

	const SIZE_UNIT_KB = 1024; //2e10
	const SIZE_UNIT_MB = 1048576; //2e20
	const SIZE_UNIT_GB = 1073741824; //2e30
	const SIZE_UNIT_TB = 1099511627776; //2e40
	const SIZE_UNIT_PB = 1125899906842624; //2e50

	/**
	* Formats size of file according to required size unit
	*
	* @param $size int size to format in bytes
	* @param $sizeUnit Int representing size unit according to constants of this class
	* @param $dynamicSizeFormat Bool if required re-aplying formating size by size unit until range of formated size is greater then choosen size unit, adequately changing unit multiple
	* @return array humanly formated size
	*/
	public static function makeFormatedSize($size, $sizeUnit = self::SIZE_UNIT_KB, $dynamicSizeFormat = FALSE, $absolutePrecision = FALSE)
	{
		if ($sizeUnit < 1) {
			throw new Exception('Base size multiplier has to be greater then zero',E_USER_WARNING);
			$sizeUnit = self::SIZE_UNIT_KB;
		}
		if ($sizeUnit !== 1 && (($sizeUnit % self::SIZE_UNIT_KB) !== 0)) {
			throw new Exception('Base size multiplier has to be valid size unit size',E_USER_WARNING);
			$sizeUnit = self::SIZE_UNIT_KB;
		}
		do {
			$formatedSize = $size / $sizeUnit;
			if (!$absolutePrecision) {
				$formatedSize = round($formatedSize, 3);
			}
			if ($dynamicSizeFormat) {
				if (($formatedSize / self::SIZE_UNIT_KB) < 1 || ($formatedSize > pow(self::SIZE_UNIT_KB,5))) {//there is no reason to format again by moving decimal point
					$dynamicSizeFormat = FALSE;
				} else {
					$sizeUnit *= self::SIZE_UNIT_KB; //formating will be processed again by moving decimal point of three digit places
				}
			}
		} while($dynamicSizeFormat);
		switch(log($sizeUnit,self::SIZE_UNIT_KB)){
			case 0:
				$symbol = '';
				break;
			case 1:
				$symbol = 'Ki';
				break;
			case 2:
				$symbol = 'Mi';
				break;
			case 3:
				$symbol = 'Gi';
				break;
			case 4:
				$symbol = 'Ti';
				break;
			case 5:
				$symbol = 'Pi';
				break;
			default:
				throw new Exception('Size of file is too large to format',E_USER_NOTICE);
		}

		return array('size'=>$formatedSize, 'unit' => $symbol . 'B');
	}
}