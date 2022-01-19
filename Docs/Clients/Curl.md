#initialize

```
$mtmPath	= realpath("/some/path/to/MTM/libs");
require_once $mtmPath."/mtm-http/Enable.php";
require_once $mtmPath."/mtm-utilities/Enable.php";
$cObj		= \MTM\Http\Factories::getClients()->getCurl()->getNewClient();
```

## Methods:


### setType:

```
$type		= "get"; ## "get", "post", "put", "delete"
$result	= $cObj->setType($type);
$result; ## apiObj
```

### addHeader:

```

//Example 1:
$name		= "Authorization";
$value		= "myemail@example.com";
$result	= $cObj->addHeader($name, $value);
$result; ## apiObj

//Example 2:
$name		= "Content-Type";
$value		= "application/json";
$result	= $cObj->addHeader($name, $value);
$result; ## apiObj
```

### addData:

```
$name		= "hostname";
$value		= "myhost.example.com";
$result	= $cObj->addData($name, $value);
$result; ## apiObj
```

### setData:

```
$myData	= array("index" => "data");
$result	= $cObj->setData($myData);
$result; ## apiObj
```


### setData:

```
$certPath	= "/etc/pki/tls/certs/myCert.pem"; //Can also be a text blob of the cert itself or a fileObj
$result	= $cObj->loadCA($certPath);
$result; ## apiObj
```
