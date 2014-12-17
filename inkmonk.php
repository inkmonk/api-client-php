<?php

require_once 'lib/Requests/library/Requests.php';



class Inkmonk{

	private static $version;
	private static $url;
	private static $key;
	private static $secret;
	public static $site;
	private static $mime_type = 'application/json';

	public static function get_signed_authorization_header($public_key, $private_key, $message){
		return base64_encode($public_key.':'.hash_hmac(
			'sha1', $message, $private_key, FALSE));
	}

	static function init(){
		Requests::register_autoloader();
		$ini = parse_ini_file("inkmonk.ini");
		self::$version = $ini['API_VERSION'];
		self::$url = $ini['API_URL'];
		self::$key = $ini['API_KEY'];
		self::$secret = $ini['API_SECRET'];
		self::$site = $ini['SITE_URL'];
	}

	static function request($method, $resource, $data=array()){
		$url = self::$url.'/'.self::$version.'/'.$resource;
		$headers = array(
			'Content-Type' => self::$mime_type,
			'Authorization' => self::get_signed_authorization_header(
				  self::$key, self::$secret,
				  $method.":".self::$version."/".$resource.":".self::$mime_type));
		if($method == 'GET'){
			$response = Requests::get($url, $headers);	
		}elseif($method == 'POST'){
			$response = Requests::post($url, $headers, json_encode($data));
		}
		else{
			throw new Exception("Unsupported Request Method");
		}

		if($response->success){
			return json_decode($response->body, true);	
		}else{
			throw new Exception("Got Exception: ".$response->body["error"]);
		}
	}

	static function all($resource){
		return self::request('GET', $resource);
	}

	static function create($resource, $data){
		return self::request('POST', $resource, $data);
	}

	static function get($resource, $identifier){
		return self::request('GET', $resource."/".$identifier);
	}

}

abstract class Inkmonk_Resource{

	static function is_assoc($array) {
	  return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	static protected $resource;

	function __construct(array $params = array()){
		foreach($params as $attr=>$value){
			$this->$attr = $value;
		}
	}

	static function all(){
		$response = Inkmonk::all(static::$resource);
		if($response['status']=='success'){
			$olist = array();
			foreach($response['result'] as $obj_params){
				$olist[] = new static($obj_params);
			}
			return $olist;
		}
		return array();
	}

	static function get($identifier){
		$response = Inkmonk::get(static::$resource, $identifier);
		if($response['status']=='success')
			return new static($response['result']);
	}

	static function create($data){
		$response = Inkmonk::create(static::$resource, $data);
		if($response['status']=='success'){
			if(self::is_assoc($response['result'])){
				return new static($response['result']);
			}
			else{
				$result=array();
				foreach($response['result'] as $item){
					$result[] = new static($item);
				}
				return $result;
			}
		}
	}
}

class Inkmonk_Merchandise extends Inkmonk_Resource{
	static protected $resource = 'merchandise';

	function __construct(array $params = array()){
		parent::__construct($params);
		$this->skus = array();
		foreach($params["skus"] as $sku){
			array_push($this->skus, new Inkmonk_SKU($sku));
		}
	}
}

class Inkmonk_SKU extends Inkmonk_Resource{
	static protected $resource = 'skus';
}

class Inkmonk_Customer extends Inkmonk_Resource{
	static protected $resource = 'customers';
}

class Inkmonk_Shipment extends Inkmonk_Resource{
	static protected $resource = 'shipments';
}

class Inkmonk_Claim extends Inkmonk_Resource{
	static protected $resource = 'claims';

	function __construct(array $params = array()){
		parent::__construct($params);
		$this->url = Inkmonk::$site.$this->url;
	}

	static function create($data){
		for($j=0; $j<count($data['slots']); $j++){
			if($data['slots'][$j][0] instanceof Inkmonk_Merchandise){
				$sku_ids=array();
				foreach($data['slots'][$j][0]->skus as $sku){
					$sku_ids[] = $sku->id;
				}
				$data['slots'][$j][0] = $sku_ids;
			}
			else{
				for($i=0; $i<count($data['slots'][$j][0]); $i++){
					if($data['slots'][$j][0][$i] instanceof Inkmonk_SKU){
						$data['slots'][$j][0][$i] = $data['slots'][$j][0][$i]->id;
					}
				}
			}
		}
		return parent::create($data);	
	}
}

