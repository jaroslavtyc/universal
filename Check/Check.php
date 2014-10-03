<?php
namespace universal\Check;

abstract class Check extends \universal\BaseClass {

	const RELEVANCE_IRRELEVANT = 'irelevant';
	const RELEVANCE_IRRELEVANT_BINCODE = 0;
	const RELEVANCE_RECOMMENDED = 'recommended';
	const RELEVANCE_RECOMMENDED_BINCODE = 1;
	const RELEVANCE_REQUIRED = 'required';
	const RELEVANCE_REQUIRED_BINCODE = 10;

	/**
	* Short name of check type, respective type of technology, as css, js etc
	*
	* @var string
	*/
	protected $code;
	/**
	* Binary representation of relevance levels allowed to check out
	*
	* @var int
	*/
	protected $allowedRelevance;
	/**
	* String expression of relevance of checking, from this is determinited binary representation of relevance
	*
	* @var String
	*/
	protected $relevance;
	/**
	* Binary representation of setted relevance
	*
	* @var int
	*/
	protected $binRelevance;

	/**
	* @param $code string short name of check type
	* @param $allowedRelevance int binary representation of alowed check relevance, should contain binary combination of more relevenace levels
	* @return void
	*/
	protected function __construct($code, $allowedRelevance)
	{
		$this->readableAll();//every protected property is set to readable (readonly)
		if (empty($code))
			throw new Exception('Code of check needs to be fullfiled');
		if (!is_string($code) && (!$code || (string($code) != $code)))
			throw new Exception('Code has to be expressible by string');
		$this->code = $code;
		if ($allowedRelevance == self::RELEVANCE_RECOMMENDED)
			$allowedRelevance = self::RELEVANCE_RECOMMENDED_BINCODE;
		if ($allowedRelevance == self::RELEVANCE_REQUIRED)
			$allowedRelevance = self::RELEVANCE_REQUIRED_BINCODE;
		if (!(($allowedRelevance & self::RELEVANCE_RECOMMENDED_BINCODE) | ($allowedRelevance & self::RELEVANCE_REQUIRED_BINCODE)))
			throw new Exception('No supported relevance has been allowed');
		$this->allowedRelevance = $allowedRelevance;
	}

	/**
	* @param $relevance string expression of avaiable relevance levels
	* @throw Exception in cases of unknown relevance tried to set on
	* @return void
	*/
	public function setRelevance($relevance)
	{
		switch(strtolower($relevance)){
			case self::RELEVANCE_RECOMMENDED :
				if (!($this->allowedRelevance & self::RELEVANCE_RECOMMENDED_BINCODE))
					throw new Exception('Not allowed relevance for ' . $this->code);
				$this->relevance = self::RELEVANCE_RECOMMENDED;
				$this->binRelevance = self::RELEVANCE_RECOMMENDED_BINCODE;
				break;
			case self::RELEVANCE_REQUIRED :
				if (!($this->allowedRelevance & self::RELEVANCE_REQUIRED_BINCODE))
					throw new Exception('Not allowed relevance for ' . $this->code);
				$this->relevance = self::RELEVANCE_REQUIRED;
				$this->binRelevance = self::RELEVANCE_REQUIRED_BINCODE;
				break;
			case self::RELEVANCE_IRRELEVANT :
				$this->relevance = self::RELEVANCE_IRRELEVANT;
				$this->binRelevance = self::RELEVANCE_IRRELEVANT_BINCODE;
				break;
			default:
				throw new Exception('Unknown relevance "' . $relevance . '" for ' . $this->code);
		}
	}

	/**
	* Checks if represented technology is supported
	*
	* @return bool token about supporting this type of technology
	*/
	abstract public function isSupported();

	/**
	* Informs about possibility of check of represented technology
	*
	* @return bool
	*/
	abstract public function isCheckable();

}
