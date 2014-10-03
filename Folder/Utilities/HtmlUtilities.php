<?php
namespace universal\Folder\Utilities;

class HtmlUtilities {

	const CONTENT_TYPE_PLAINT_TEXT = 'plain/text';
	const CONTENT_TYPE_TEXT_JAVASCRIPT = 'type="text/javascript"';
	const CONTENT_TYPE_EXCEL = 'application/vnd.ms-excel';
	const DEFAULT_CONTENT_TYPE = self::CONTENT_TYPE_PLAINT_TEXT;

	public static function sendFileDownloadHeader(
	  $downloadFilename, $contentType = self::DEFAULT_CONTENT_TYPE) {

		if (!empty($contentType)
		 || preg_match('~\.[^.]+$', $downloadFilename, $suffix)) {
			if (empty($sufix[0])) {
				$contentType = self::DEFAULT_CONTENT_TYPE;
			} else {
				switch($sufix[0]) {
					case 'xls':
						$contentType = self::CONTENT_TYPE_EXCEL;
						break;
					case 'txt':
						$contentType = self::CONTENT_TYPE_PLAINT_TEXT;
						break;
					default:
						$contentType = self::DEFAULT_CONTENT_TYPE;
				}
			}
		}

		header('Content-type: ' . $contentType);
		header('Content-Disposition: attachment;filename="' . urlencode($downloadFilename) . '"');
	}

	public static function download($source, $downloadFileName, $contentType = NULL) {
		if (is_resource($source)) {
			throw new Exception('Data source of type "resource" is not supported yet');
		}
		if (is_string($source)) {
			if (file_exists($source) && is_file($source)) {
				if (($resource = fopen($source,'r'))) {
					$data = '';
					while (!feof($resource)) {
						$data .= fread($resource, \universal\Folder\Utilities\FileUtilities::SIZE_UNIT_KB);
					}
					self::sendFileDownloadHeader($downloadFileName);
					echo $data;
				} else {
					throw Exception('File could not be opened', E_USER_WARNING);
				}
			} else {
				self::sendFileDownloadHeader($downloadFileName, $contentType);
				echo $source;
			}
		} else {
			throw new Exception('Unknown data source');
		}
	}
}