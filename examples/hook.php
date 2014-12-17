<?php

include('../inkmonk.php');

Inkmonk::init();

$file = 'hook.txt';

/*A sample hook handler. You can directly use the snippets used here. */

$current = file_get_contents($file);

$current.= "POST DATA: \n";
$current.= "category = ".$_POST['category']."\n";
$current.= "resource = ".$_POST['resource']."\n";
$current.= "identifier = ".$_POST['identifier']."\n";

$current.= "INCOMING SIGNATURE: ".$_SERVER["HTTP_X_HOOK_SIGNATURE"]."\n";

$current.= "OUR SIGNATURE: ".Inkmonk::get_x_hook_signature($_POST)."\n";


//Use the below snippet to verify the incoming request and carry out an action
if(Inkmonk::verify_signature($_SERVER, $_POST)){
	if($_POST["category"]=="claim_redemption"){
		if(array_key_exists("instance", $_POST)){
			$current.="Fetching the data from result itself\n";
			$claim = new Inkmonk_Claim(json_decode($_POST["instance"], true));
		}
		else{
			$current.="Fetching the data from server\n";
			$claim = Inkmonk_Claim::get($_POST["identifier"]);
		}
		$current.="Claim converted to shipment id: ".$claim->url."\n";
	}
}

file_put_contents($file, $current);

