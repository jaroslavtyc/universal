<?php
namespace universal;

/**
 * Every setting is keeped in separated class, based on this model
 */
abstract class SingleHtmlOptionModel extends HtmlInputModel {

	public function __construct() {
		parent::__construct(array());
	}
}