<?php
namespace universal;

/**
 * Adaptable class adopting properties and methods of given classes,
 * giving option to combine more classes toghether as multi-inheritance in one
 * generation
 *
 * @author Jaroslav Týc
 */
class AdoptingClass extends IterableTycClass {

	public function addPatternClass($classInstance){
		if (!is_object($classInstance)) {
			throw new Exception('Pattern has to be an instance of class');
		}
		var_dump($classInstance); //public only or all variables and methods?
		parent::__construct(array());
	}
}