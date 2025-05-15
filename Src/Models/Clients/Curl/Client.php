<?php
//� 2017 Martin Madsen
namespace MTM\Http\Models\Clients\Curl;

class Client
{
	protected $_parentObj=null;
	protected $_isAsync=false;
	protected $_curl=null;
	protected $_multiCurl=null;
	protected $_url=null;
	protected $_headers=array();
	protected $_reqType="get";
	protected $_reqData=array();
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
	public function addHeader($name, $value)
	{
		$this->_headers[$name]	= $value;
		$this->setHeaders($this->_headers);
	}
	public function setHeaders($heads)
	{
		//e.g.
		//$heads						= array();
		//$heads["Authorization"]		= $email . " " . $token;
		$this->_headers	= $heads;
		$headers		= array();
		foreach ($this->_headers as $name => $value) {
			$headers[]				= $name . ": " .  $value;
		}
		curl_setopt($this->getCurl(), CURLOPT_HTTPHEADER, $headers);
		return $this;
	}
	public function getHeaders()
	{
		return $this->_headers;
	}
	public function setType($str)
	{
		if (in_array($str, array("get", "post", "put", "delete")) === true) {
			$this->_reqType	= $str;
			return $this;
		} else {
			throw new \Exception("Invalid request type: " . $str);
		}
	}
	public function addData($key, $value)
	{
		$this->_reqData[$key]	= $value;
		return $this;
	}
	public function setData($data)
	{
	    //mixed input i.e:
	    //$data	= array("hostname" => "myhost.example.com", "ipAddress" => "192.168.1.1");
	    //$data	= "some string";
		$this->_reqData	= $data;
	    return $this;
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
		$filePath	= null;
		if (is_object($cert) === true && $cert->getExists() === true) {
			$filePath	= $cert->getPathAsString();
		} elseif (is_string($cert) === true && $cert != "") {
			if (preg_match("/^(\/|[A-Z]{1,2}\:)/i", $cert) == 1 && file_exists($cert) === true) {
				$filePath	= $cert;
			} else {
				$fileObj	= \MTM\FS\Factories::getFiles()->getTempFile("pem");
				$fileObj->setContent($cert);
				$filePath	= $cert->getPathAsString();
			}
			
		} else {
			throw new \Exception("Certificate not valid");
		}
	    
		curl_setopt($this->getCurl(), CURLOPT_CAINFO, $filePath);
		return $this;
	}
	public function execute($throw=true)
	{
		curl_setopt($this->getCurl(), CURLOPT_CUSTOMREQUEST, null);
		curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, array());
		curl_setopt($this->getCurl(), CURLOPT_POST, 0);
		curl_setopt($this->getCurl(), CURLOPT_URL, "");

		$this->_lastData	= null;
		if ($this->_reqType == "get") {
			$attrs	= "";
			foreach ($this->_reqData as $key => $value) {
				$attrs	.= $key . "=" . $value . "&";
			}
			$attrs	= trim($attrs, "&");
			$url	= $this->getUrl();
			if (strpos($url, "?") !== false) {
				$lastChar	= substr($url, -1);
				if ($lastChar != "?") {
					if ($lastChar != "&") {
						$url	= $url . "&";
					}
				}
			} else {
				$url	= $url . "?";
			}
			$url	= $url . $attrs;
			curl_setopt($this->getCurl(), CURLOPT_URL, $url);
			
		} elseif ($this->_reqType == "post") {
			
			curl_setopt($this->getCurl(), CURLOPT_URL, $this->getUrl());
			curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $this->_reqData);
			curl_setopt($this->getCurl(), CURLOPT_POST, 1);
			
		} elseif ($this->_reqType == "put") {
			
			if (is_array($this->_reqData) === true) {
				$data	= http_build_query($this->_reqData);
			} else {
				$data	= $this->_reqData;
			}
			curl_setopt($this->getCurl(), CURLOPT_URL, $this->getUrl());
			curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $data);
			curl_setopt($this->getCurl(), CURLOPT_CUSTOMREQUEST, "PUT");
		} elseif ($this->_reqType == "delete") {
			
			if (is_array($this->_reqData) === true) {
				$data	= http_build_query($this->_reqData);
			} else {
				$data	= $this->_reqData;
			}
			curl_setopt($this->getCurl(), CURLOPT_URL, $this->getUrl());
			curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $data);
			curl_setopt($this->getCurl(), CURLOPT_CUSTOMREQUEST, "DELETE");
		} else {
			throw new \Exception("Not handled for request type: " . $this->_reqType);
		}
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
	public function getCurlError()
	{
		if ($this->_curl !== null) {
			return curl_error($this->_curl);
		} else {
			return "No Curl";
		}
	}
	protected function getCurl()
	{
		if ($this->_curl === null) {
			$this->_curl	= curl_init();
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
		if ($this->_curl !== null) {
			curl_close($this->_curl);
			$this->_curl	= null;
		}
		if ($this->_multiCurl !== null) {
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