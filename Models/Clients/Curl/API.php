<?php
//© 2017 Martin Madsen
namespace MTM\Http\Models\Clients\Curl;

class API
{
	protected $_s=array();

	public function getNewClient()
	{
		$newClient	= new \MTM\Http\Models\Clients\Curl\Client();
		$newClient->setParent($this);
		return $newClient;
	}
	public function getCurlInstance()
	{
		$ch		= curl_init();
		return $ch;
	}
}