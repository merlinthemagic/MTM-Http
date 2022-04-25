<?php
//© 2019 Martin Peter Madsen
namespace MTM\Http\Factories;

class Clients extends Base
{
	public function getCurl()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Http\Models\Clients\Curl\API();
		}
		return $this->_s[__FUNCTION__];
	}
}