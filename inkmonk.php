<?php

require_once 'lib/Requests/library/Requests.php';



class Inkmonk{

	public static $version;
	public static $url;
	public static $key;
	public static $secret;
	public static $site;
	public static $mime_type = 'application/json';

	public static function get_x_hook_signature($post){
		$message=self::$key.":".$post["category"].":".$post["resource"].":".$post["identifier"];
		return hash_hmac('sha1', $message, self::$secret, FALSE);
	}

	public static function verify_signature($server, $post){
		return $server["HTTP_X_HOOK_SIGNATURE"] == self::get_x_hook_signature($post);
	}

	public static function get_signed_authorization_header($public_key, $private_key, $message){
		return base64_encode($public_key.':'.hash_hmac(
			'sha512', $message, $private_key, FALSE));
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
				  $method.":"."/".self::$version."/".$resource.":".self::$mime_type));
		if($method == 'GET'){
			$response = Requests::get($url, $headers);	
		}elseif($method == 'POST'){
			$response = Requests::post($url, $headers, json_encode($data));
		}
		else{
			throw new Exception("Unsupported Request Method");
		}
		if($response->status_code==200||$response->status_code==201||$response->status_code==204){
			return json_decode($response->body, true);	
		}else{
			var_dump($response->status_code);
			throw new Exception("Got Exception: ".$response->body);
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
		if(array_key_exists("skus", $params)){
			foreach($params["skus"] as $sku){
				array_push($this->skus, new Inkmonk_SKU($sku));
			}
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

	static function create($data){
		for($j=0; $j<count($data['slots']); $j++){
			if($data['slots'][$j]["choices"] instanceof Inkmonk_Merchandise){
				if(array_key_exists("skus", $data['slots'][$j]["choices"])){
					$sku_ids=array();
					foreach($data['slots'][$j]["choices"]->skus as $sku){
						$sku_ids[] = $sku->id;
					}
					$data['slots'][$j]["choices"] = $sku_ids;
				}
				else{
					$data['slots'][$j]["choices"] = [$data['slots'][$j]["choices"]->id];
				}
			}
			else{
				for($i=0; $i<count($data['slots'][$j]["choices"]); $i++){
					if($data['slots'][$j]["choices"][$i] instanceof Inkmonk_SKU||
						$data['slots'][$j]["choices"][$i] instanceof Inkmonk_Merchandise){
						$data['slots'][$j]["choices"][$i] = $data['slots'][$j]["choices"][$i]->id;
					}
				}
			}
		}
		return parent::create($data);	
	}
}

