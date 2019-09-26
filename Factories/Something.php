<?php
//© 2019 Martin Peter Madsen
namespace MTM\Http\Factories;

class Something extends Base
{
	public function getSomeTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \stdClass();
		}
		return $this->_cStore[__FUNCTION__];
	}
}