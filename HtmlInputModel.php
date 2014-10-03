<?php
namespace universal;

/**
 * Every setting is keeped in separated class, based
 * on this model
 */
abstract class HtmlInputModel extends IterableTycClass {

	protected $humanName;
	protected $code;
	protected $value;

	/**
	 * Accept list of any options, belonging to intput
	 *
	 * @param array $options
	 */
	public function __construct($options) {
		$this->setSettings();
		$this->makePropertiesReadable('humanName', 'code', 'value');
		parent::__construct($options);
	}

	/**
	 * Sets properties by values of descendent constants
	 */
	protected function setSettings() {
		if (!defined('static::HUMAN_NAME') || !defined('static::CODE')
		 || !defined('static::VALUE')) {
			throw new Exception('Constants are not set up in class ' . get_class($this));
		}

		$this->humanName = static::HUMAN_NAME;
		$this->code = static::CODE;
		$this->value = static::VALUE;
	}
}