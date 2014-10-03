<?php
/**
 * Tools for working with objects
 *
 * @author Jaroslav Týc <mail@jaroslavtyc.com>
 */
class ObjectUtilities {

	/**
	 * Gets properties, visible from actual scope, that means public
	 *
	 * @param object $object
	 * @return array list of properties 
	 */
	public static function getObjectPublicProperties(object $object) {
		return get_object_vars($object);
	}
}