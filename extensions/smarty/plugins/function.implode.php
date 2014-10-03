<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Tyc implode array modifier plugin
 * 
 * Type:			modifier
 * Name:			implode
 * Purpose:		concatenate values from an array, separating them by optional delimiter
 * 
 * @param array $inputArray input array
 * @param string $delimiter character(s) to put between catenated fragments
 * @param bool $useKeysToo if keys of array should be put into result string
 * @param string $keyValueDelimiter character(s) to put between key and value, if $useKeysToo is used
 * @return string 
 */
function smarty_modifier_implode($inputArray, $delimiter = '', $useKeysToo = FALSE, $keyValueDelimiter = ':')
{
	if (!is_array($inputArray)) {
		return $inputArray;
	}

   if (!$useKeysToo) {
		return implode($delimiter, $inputArray);
	}
	
	$resultString = '';
	foreach ($inputArray as $key => $value) {
		if ($resultString != '') {
			$resultString .= $delimiter;
		}
		
		$resultString .= $key . $keyValueDelimiter . $value;
	}
	
	return $resultString;
}