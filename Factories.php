<?php
//© 2019 Martin Peter Madsen
namespace MTM\Http;

class Factories
{
	private static $_cStore=array();
	
	//USE: $aFact		= \MTM\Http\Factories::$METHOD_NAME();
	
	public static function getSomething()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\Http\Factories\Something();
		}
		return self::$_cStore[__FUNCTION__];
	}
}