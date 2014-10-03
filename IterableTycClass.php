<?php
namespace universal;

/**
 * As simple iterable class as possible
 * Works as wraper for an array, providing append of properties
 * and methods around as any class in case of inheritance
 */
class IterableTycClass extends BaseClass implements \Iterator {

	/**
	 * @var array list of elements to iterate
	 */
	protected $data;

	/**
	 * @var mixed index of actual data position
	 */
	protected $currentKey = NULL;

	/**
	 * Adds main array and restart pointer to first element
	 *
	 * @param array $data list of items to walk throught
	 */
	public function __construct($data) {
		$this->setData($data);
		$this->rewind();
	}

	/**
	 * Inner setter for input array with data type check
	 *
	 * @param array $data
	 */
	private function setData($data) {
		if (!is_array($data)) {
			throw new Exception('Data for iteration have to be an array');
		}
		$this->data = $data;
	}

	/**
	 * Getter for data property
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Resets pointer on array to first element
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->data);
		$this->currentKey = $this->key();
	}

	/**
	 * Gives item from data array on actual position
	 *
	 * @return mixed item from data array
	 */
	public function current() {
		return current($this->data);
	}

	/**
	 * Gives index item from data array on pointer position
	 *
	 * @return mixed index on actual position of item from data array
	 */
	public function key() {
		return key($this->data);//returns NULL if out of array
	}

	/**
	 * Moves pointer to next item in data array
	 *
	 * @return void
	 */
	public function next() {
		next($this->data);
		$this->currentKey = $this->key();//if out of array, sets NULL
	}

	/**
	 * Check if key of actual position in data array is set.
	 * Uses NULL "value", don't use it as array key, otherwise
	 * unexpected behavior occurs
	 *
	 * @return bool existence of element on current pointer position
	 */
	public function valid() {
		return $this->currentKey !== NULL;
	}

	/**
	 * Check if actual position is last
	 *
	 * @return bool
	 */
	public function isLast() {
		next($this->data);
		$isLast = (NULL === key($this->data));
		prev($this->data);

		return $isLast;
	}

	/**
	 * Reads number of items in data storage
	 *
	 * @return int
	 */
	public function getSize() {
		return sizeof($this->data);
	}
}