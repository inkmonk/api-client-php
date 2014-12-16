<?php

require_once 'lib/Requests/library/Requests.php';

class Inkmonk{

	private static $version;
	private static $url;
	private static $key;
	private static $secret;
	private static $mime_type = 'application/json';

	private static function get_signed_authorization_header($public_key, $private_key, $message){
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
	}

	static function request($method, $resource){
		$url = self::$url.'/'.self::$version.'/'.$resource;
		$headers = array(
			'Content-Type' => self::$mime_type,
			'Authorization' => self::get_signed_authorization_header(
				  self::$key, self::$secret,
				  "GET".":".self::$version."/".$resource.":".self::$mime_type));
		$response = Requests::get($url, $headers);
		return json_decode($response->body, true);
	}

	static function all($resource){
		return self::request('GET', $resource);
	}

	static function get($resource, $identifier){
		return self::request('GET', $resource."/".$identifier);
	}

}

abstract class Resource{

	static protected $resource;

	protected function __construct(array $params = array()){
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
}

class Merchandise extends Resource{
	static protected $resource = 'merchandise';
}

class Sku extends Resource{
	static protected $resource = 'skus';
}

class Customer extends Resource{
	static protected $resource = 'customers';
}

class Shipment extends Resource{
	static protected $resource = 'shipments';
}

class Claim extends Resource{
	static protected $resource = 'claims';
}

