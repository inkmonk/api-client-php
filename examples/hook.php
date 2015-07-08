<?php

include('../inkmonk.php');

Inkmonk::init();

$file = 'hook.txt';


/*
Your POST endpoint can be configured to either accept just a notification and 
then make a request back to our server to fetch the data. Or it can also directly
accept the data. This setting can be made from our dashboard.
In both cases, your endpoint should accept 3 keys

`category` - The category of the notification/data being sent. Eg: 'claim_redemption'
`resource` - The type of resource that is being notified. Eg: 'claims'
`identifier` - The 'id' of the object that you can use to request the resource back
If you call Inkmonk::get(resource, idenitifier), you will get the raw output as an 
associative array. But you can use our Resource classes as used in the snippet below
to get the output as an object

In case you have made the setting in our dashboard to accept full data, we will be 
sending one more key 
`data` - This will contain the full data about the instance of the resource that
is being sent. If it is a claim. It will be the full JSON structure of a claim as listed
in our API. You can then save this data as you prefer.
*/

/*A sample hook handler. You can directly use the snippets used here.
The snippets log the incoming data to a text file. You will probably replace
those lines with some DB handler code to save it to your DB. */


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
		if(array_key_exists("data", $_POST)){
			$current.="Fetching the data from result itself\n";
			$json = json_decode($_POST["data"], true);
			$claim = new Inkmonk_Claim($json);
			var_dump($json);
		}
		else{
			$current.="Fetching the data from server\n";
			$claim = Inkmonk_Claim::get($_POST["data"]);
		}
		$current.="Claim for customer ".$claim->reference." converted to shipment id: ".$claim->shipment_id."\n";
	}
}

file_put_contents($file, $current);

