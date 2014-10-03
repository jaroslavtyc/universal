<?php
namespace universal\Folder\File\Xls;

/**
 * Formats two-dimensional array into html table marked as Microsoft Excel
 */
class HtmlXlsFile extends \universal\IterableTycClass {

	protected $header = FALSE;
	protected $originalHeaderKey;
	protected $footer = FALSE;
	protected $originalFooterKey;

	/**
	 * @param array $data two-dimensional array with table row data
	 */
	public function __construct($data = array()) {
		$tableRows = $this->createTableRows($data);
		try {
			parent::__construct($tableRows);
		} catch (Exception $e) {
			throw new Exception('Input data has to be an array', E_USER_ERROR);
		}

		$this->makePropertiesReadable('header','footer');
	}

	public function addRow(HtmlXlsRow $row) {
		$this->data[] = $row;
	}

	/**
	 * Build html table from data and return it
	 *
	 * @return string builded HTML table with XLS header
	 */
	public function getTable() {
		if ($this->data) {
			$actualPosition = $this->currentKey;
		}

		$smarty = \universal\View\Smarty::get();
		$smarty->assign('data', $this);
		$table = $smarty->fetch('export/html_xls.tpl');

		$this->rewind();
		if (isset($actualPosition)) {
			while ($actualPosition !== $this->currentKey) {
				$this->next();
				if (!$this->valid()) {
					throw new Exception ('Original index of data was not found on restore in function ' . __FUNCTION__);
				}
			}
		}

		return $table;
	}

	/**
	 *
	 * @param string $bgcolor HTML color of row background
	 * @param mixed $rowIndex index of row to set the color
	 * @return bool
	 */
	public function setBgcolorToRow($bgcolor, $rowIndex) {
		if (!isset($this->data[$rowIndex])) {
			throw new Exception('Row with given index was not found', E_USER_NOTICE);
		}

		return $this->data[$rowIndex]->setBgcolor($bgcolor);
	}

	/**
	 *
	 * @param string $bgcolor HTML color of row background
	 * @param mixed $rowIndex index of row to set the color
	 * @return bool
	 */
	public function setBgcolorToHeader($bgcolor) {
		if (!isset($this->header)) {
			throw new Exception('Header is not set', E_USER_NOTICE);
		}

		return $this->header->setBgcolor($bgcolor);
	}

	/**
	 * Separate first row as header of final table
	 *
	 * @return bool if moving was successful
	 */
	public function moveFirstRowAsHeader() {
		if (sizeof($this->data) == 0) {
			return FALSE;
		}

		//save actual position on data for later return to same position
		$actualKey = key($this->data);
		$this->rewind(); //reseting data to first position
		//saving first row key for case of returing header into data
		$this->originalHeaderKey = key($this->data);
		// saving first row separately
		$this->header = current($this->data);
		//removing first row from data list - is not part of table body data
		// anymore
		unset($this->data[$this->originalHeaderKey]);
		//pointer originaly pointed to first row,
		// rewind will move pointer to new first row
		$this->rewind();
		//previous pointer position was not on first row
		if ($actualKey !== $this->originalHeaderKey) {
			while($this->currentKey !== $actualKey) {
				$this->next();
				if (!$this->valid()) {
					throw new Exception ('Original index of data was not found on restore in function ' . __FUNCTION__);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Puts header back into table data
	 *
	 * @return bool if moving was successful
	 */
	public function moveHeaderAsFirstRow() {
		if (empty($this->header) || !is_array($this->header)) {

			return FALSE;
		}

		if (!isset($this->originalHeaderKey)) {
			throw new Exception('No original key of actual header from data array is known');
		}

		if (!intval($this->originalHeaderKey) != $this->originalHeaderKey
		  && isset($this->data[$this->originalHeaderKey])) {
			throw new Exception('Original index of actual header row is already used in data array');
		}

		//save actual position on data for later return to same position
		$actualKey = key($this->data);
		$this->rewind(); //reseting data to first position
		//saving first row key for comparation with actual key
		$firstKey = key($this->data);
		$this->data = array_merge(
			$this->data,
			array($this->originalHeaderKey => $this->header)
		);
		$this->rewind();
		if ($actualKey !== $firstKey) {
			while($this->currentKey !== $actualKey) {
				$this->next();
				if (!$this->valid()) {
					throw new Exception ('Original index of data was not found after restore in function ' . __FUNCTION__);
				}
			}
		}

		//dismark previous header, actual first row, as header
		$this->current()->setIsHeader(FALSE);
		// unseting header property
		$this->header = NULL;
		// unseting index of header in data
		$this->originalHeaderKey = NULL;

		return TRUE;
	}

	/**
	 * Separate last row as footer of final table
	 *
	 * @return bool if moving was successful
	 */
	public function moveLastRowAsFooter() {
		if (sizeof($this->data) == 0) {
			return FALSE;
		}

		//save actual position on data for later return to same position
		$actualKey = key($this->data);
		end($this->data); //reseting data to last position
		//saving last row key for case of returing footer into data
		$this->originalFooterKey = key($this->data);
		// saving last row separately
		$this->footer = current($this->data);
		//removing last row from data list - is not part of table body data
		// anymore
		unset($this->data[$this->originalFooterKey]);
		//pointer originaly pointed to last row,
		// rewind will move pointer to new last row
		$this->rewind();
		//previous pointer position was not on first row
		if ($actualKey !== $this->originalFooterKey) {
			while($this->currentKey !== $actualKey) {
				$this->next();
				if (!$this->valid()) {
					throw new Exception ('Original index of data was not found on restore in function ' . __FUNCTION__);
				}
			}
		}

		return TRUE;
	}

	//-------- INNER FUCNTIONS --------

	/**
	 * @param array $data two-dimansional array with data for table rows
	 * @return array with HtmlXlsRow objects representing table rows
	 */
	protected function createTableRows($data) {
		if (!is_array($data)) {
			throw new Exception('Data for table rows have to be an array');
		}

		$tableRows = array();
		foreach ($data as $rowIndex => $dataForRow) {
			$tableRows[$rowIndex] = new HtmlXlsRow($dataForRow);
		}

		return $tableRows;
	}
}