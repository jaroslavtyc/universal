<?php
namespace universal;

class Word extends BaseClass {

	/**
	* @var $content string with wording in base case
	*/
	protected $content;

	/**
	* @var $case string representing actual word shape level proper to language
	*/
	protected $case;

	/**
	* @var $language object of class Language
	*/
	protected $language;

	public function __construct($content, Language $language) {}
}