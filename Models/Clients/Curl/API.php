<?php
//© 2017 Martin Madsen
namespace MTM\Http\Models\Clients\Curl;

class API
{
	protected $_cStore=array();

	public function getNewClient()
	{
		$newClient	= new \MTM\Http\Models\Clients\Curl\Client();
		$newClient->setParent($this);
		return $newClient;
	}
	public function getCurlInstance($timeout=30)
	{
		$ch		= curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		//gzip header is automatically added by the CURLOPT_ENCODING option
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0");
		return $ch;
	}
}