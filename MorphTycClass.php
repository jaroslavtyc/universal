<?php
namespace universal;

/**
 * Adaptable class consuming properties and methods of given classes,
 * giving option to combine more classes toghether as multi-inheritance in one
 * generation
 *
 * @author Jaroslav Týc
 */
class MorphClass extends IterableTycClass {

	public function __construct(){

	}

	public function addPatternClass($classInstance){
		if (!is_object($classInstance)) {
			throw new Exception('Pattern class has to be an instance of class');
		}
		var_dump($classInstance); //public only or all variables and methods?
		parent::__construct(array());
	}
}