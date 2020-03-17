<?php
//© 2017 Martin Madsen
namespace MTM\Http\Models\Clients\Curl;

class Client
{
	protected $_parentObj=null;
	protected $_isAsync=false;
	protected $_curl=null;
	protected $_multiCurl=null;
	protected $_url=null;
	protected $_headers=array();
	protected $_postData=array();
	protected $_connTimeout=30;
	protected $_lastData=null;
	
	public function __destruct()
	{
		$this->closeCurl();
		$this->setParent(null);
	}
	public function setIsAsync($bool)
	{
		$this->_isAsync		= $bool;
		return $this;
	}
	public function getIsAsync()
	{
		return $this->_isAsync;
	}
	public function setUrl($url)
	{
		$this->_url		= $url;
		curl_setopt($this->getCurl(), CURLOPT_URL, $url);
		return $this;
	}
	public function setConnTimeout($secs)
	{
		if ($this->_connTimeout != $secs) {
			$this->_connTimeout		= $secs;
			if ($this->_curl !== null) {
				curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->_connTimeout);
			}
		}
		
		return $this;
	}
	public function getUrl()
	{
		return $this->_url;
	}
	public function setBasicAuthentication($username, $password)
	{
		curl_setopt($this->getCurl(), CURLOPT_USERPWD, $username . ":" . $password);
		return $this;
	}
	public function setHeaders($heads)
	{
		//e.g.
		//$heads						= array();
		//$heads["Authorization"]		= $email . " " . $token;
		
		$headers	= array();
		foreach ($heads as $name => $value) {
			$this->_headers[$name]	= $value;
			$headers[]				= $name . ": " .  $value;
		}
		curl_setopt($this->getCurl(), CURLOPT_HTTPHEADER, $headers);
		return $this;
	}
	public function getHeaders()
	{
		return $this->_headers;
	}
	public function addPostData($key, $value)
	{
		$this->_postData[$key]	= $value;
		$this->setPostData($data);
		return $this;
	}
	public function setPostData($data=null)
	{
	    //mixed input i.e:
	    //$data	= array("hostname" => "myhost.example.com", "ipAddress" => "192.168.1.1");
	    //$data	= "some string";
		$this->_postData	= $data;
	    curl_setopt($this->getCurl(), CURLOPT_POST, 1);
	    curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $data);
	    return $this;
	}
	public function getPostData()
	{
	    return $this->_postData;
	}
	public function setSslVerify($bool)
	{
	    curl_setopt($this->getCurl(), CURLOPT_SSL_VERIFYPEER, $bool);
	    return $this;
	}
	public function setVerbose($bool)
	{
	    //default is false
	    curl_setopt($this->getCurl(), CURLOPT_VERBOSE, $bool);
	    return $this;
	}
	public function loadCA($cert)
	{
		//load any intermediate or root CA, self signed is fine
		//only allowed file format is PEM
		if (is_object($cert) === true && $cert->getExists() === true) {
			$fileObj	= $cert;
		} elseif (is_string($cert) === true) {
			$fileObj	= \MHT\Factories::getFileSystems()->getSessionFile("pem");
			$fileObj->setContent($cert);
		} else {
			throw new \Exception("Certificate not valid");
		}
	    
		curl_setopt($this->getCurl(), CURLOPT_CAINFO, $fileObj->getPathAsString());
		return $this;
	}
	public function execute($throw=true)
	{
		$this->_lastData	= null;
		if ($this->getIsAsync() === true) {
			curl_multi_add_handle($this->getMultiCurl(), $this->getCurl());
			curl_multi_exec($this->getMultiCurl(), $active);
		} else {
			$this->_lastData	= curl_exec($this->getCurl());
		}
		
		return $this->getData($throw);
	}
	public function getData($throw=true)
	{
		if ($this->getIsAsync() === true) {
			$status	= curl_multi_exec($this->getMultiCurl(), $isRunning);
			if ($isRunning === 1 && $status == CURLM_OK) {
				curl_multi_select($this->getMultiCurl());
			} else {
				$this->_lastData	= curl_multi_getcontent($this->getCurl());
				curl_multi_remove_handle($this->getMultiCurl(), $this->getCurl());
			}
		}
		
		if ($this->_lastData !== null) {
			if ($throw === true) {
				$code	= $this->getCode();
				if ($code != 200) {
					throw new \Exception("Http code: " . $code);
				}
			}
		}

		return $this->_lastData;
	}
	public function getMetaInfo()
	{
		$rData	= curl_getinfo($this->getCurl());
		$hObj	= new \stdClass();
		foreach ($rData as $key => $val) {
			if (is_array($val) === false) {
				$hObj->$key	= $val;
			} else {
				$hObj->$key	= new \stdClass();
				foreach ($val as $sKey => $sVal) {
					$hObj->$key->$sKey	= $sVal;
				}
			}
		}
		return $hObj;
	}
	public function getCode()
	{
		$hObj	= $this->getMetaInfo();
		if (isset($hObj->http_code) === true) {
			return intval($hObj->http_code);
		} else {
			return null;
		}
	}
	protected function getCurl()
	{
		if ($this->_curl === null) {
			$this->_curl	= $this->getParent()->getCurlInstance();
			curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->_connTimeout);
			curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, true);
			//gzip header is automatically added by the CURLOPT_ENCODING option
			curl_setopt($this->_curl, CURLOPT_ENCODING , "gzip");
			curl_setopt($this->_curl, CURLOPT_POST, false);
			curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->_curl, CURLOPT_VERBOSE, false);
			curl_setopt($this->_curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0");
		}
		return $this->_curl;
	}
	protected function getMultiCurl()
	{
		if ($this->_multiCurl === null) {
			$this->_multiCurl	= curl_multi_init();
		}
		return $this->_multiCurl;
	}
	protected function closeCurl()
	{
		if ($this->_curl !== null && is_resource($this->_curl) === true) {
			curl_close($this->_curl);
			$this->_curl	= null;
		}
		if ($this->_multiCurl !== null && is_resource($this->_multiCurl) === true) {
			curl_multi_close($this->_multiCurl);
			$this->_multiCurl	= null;
		}
	}
	public function setParent($obj)
	{
		$this->_parentObj		= $obj;
		return $this;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
}