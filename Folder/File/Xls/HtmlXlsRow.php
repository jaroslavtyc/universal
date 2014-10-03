<?php
namespace universal\Folder\File\Xls;

class HtmlXlsRow extends \universal\IterableTycClass {

	const TABLE_BODY_ROW_TAG = 'tr';
	const TABLE_HEADER_ROW_TAG = 'th';

	/**
	 * @var string HTML color of row background
	 * !read-only
	 */
	protected $bgcolor = FALSE;

	/**
	 * @var bool token if row is part of table header
	 */
	protected $isHeader = FALSE;

	/**
	 *
	 * @param array $data one-dimensional array with data of table cells
	 * of single row
	 */
	public function __construct($data) {
		try {
			parent::__construct($data);
		} catch (Exception $e) {
			throw new Exception('Input data has to be an array');
		}

		$this->makePropertiesReadable('bgcolor', 'isHeader');
	}

	/**
	 * Mark this row as header row
	 *
	 * @param bool $isHeader
	 */
	public function setIsHeader($isHeader) {
		$this->isHeader = (bool)$isHeader;
	}

	/**
	 *
	 * @param string $bgcolor HTML color of row background
	 * - has to be in valid HTML format
	 * @return bool token about successful color seting
	 */
	public function setBgcolor($bgcolor) {
		//turn off bgcolor
		if (empty($bgcolor)) {
			$this->bgcolor = FALSE;

			return TRUE;
		}

		//bgcolor format does not match html standard
		if (!preg_match(
		  '~^(#([[:alnum:]]{3}|[[:alnum:]]{6})|rgb\(\d{1,3},\d{1,3},\d{1,3},\))$~i', $bgcolor)) {
		  throw new Exception('Background color has to be in valid HTML color format.');
		}

		$this->bgcolor = $bgcolor;

		return TRUE;
	}

	/**
	 *
	 * @return string HTML row formated as header or table row
	 */
	public function getRow() {
		//tag tr with optional BGCOLOR parameter
		$row = '<' . $this->getRowTag() .
			($this->bgcolor
				? 'BGCOLOR="' . $this->bgcolor . '"'
				: '') .
			'>';
		foreach ($this->data as $tableCellData) {
			$row .= '<td>' . htmlspecialchars($tableCellData) . '</td>';
		}

		$row .= '</' . $this->getRowTag() . '>';

		return $row;
	}

	/**
	 *
	 * @return string tag of table row in dependency of isHeader setting
	 */
	protected function getRowTag() {
		switch ($this->isHeader) {
			case TRUE:
				return self::TABLE_BODY_ROW_TAG;
			case FALSE:
				return self::TABLE_HEADER_ROW_TAG;
		}
	}
}