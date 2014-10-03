<?php
namespace universal;

class Language extends BaseClass {

	/**
	* @var $languageName string english name of language
	*/
	protected $languageName;

	/**
	* @var $languageCode string ISO 639-2 3-character representation of language
	*/
	protected $languageCode;

	/**
	* @var $localLanguageName string language name as wording in that language
	*/
	protected $localLanguageName;


	/**
	* Initialization function of class
	* @param $languageName string english name of language
	* @param $languageCode string ISO 639-2 3-character representation of language
	* @param $localLanguageName string eventual languageName name as wording in that language
	*/
	public function __construct($languageName, $languageCode, $localLanguageName = NULL){
		$this->setLanguage($languageName);
		$this->setLanguageCode($languageCode);
		$this->setLocalLanguage($localLanguageName);
	}

	protected function setLanguageName($languageName)
	{
		if (empty($languageName))
			throw new Exception('Language name can not be empty', E_USER_WARNING);
		if (!is_string($languageName))
			throw new Exception('Language name has to be represented by string', E_USER_WARNING);
		$this->languageName = trim($languageName);
	}

	protected function setLanguageCode($languageCode)
	{
		if (empty($languageCode))
			throw new Exception('Language code can not be empty', E_USER_WARNING);
		if (!is_string($languageCode) || strlen(trim($languageCode)) != 3)
			throw new Exception('Language code has to be represented by 3-character length string (ISO 639-2)', E_USER_WARNING);
		$this->languageCode = strtoupper(trim($languageCode));
	}

	protected function setLocalLanguageName($localLanguageName)
	{
		if (!empty($localLanguageName))
			if (!is_string($languageCode))
				throw new Exception('Language local name should be empty in meaning as unknown or string', E_USER_WARNING);
			else
				$this->localLanguageName = trim($localLanguageName);
	}
}