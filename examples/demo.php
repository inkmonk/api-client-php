<?php

include('../inkmonk.php');

Inkmonk::init();


$new_customer_params = array(
	"name" => "Virat Kohli",
	"email" => "vkohli1@blah.com",
	"address1" => "9, Seashell Apt",
	"address2" => "Kenneth Lane",
	"city" => "Palo Alto",
	"state" => "California",
	"country" => "US",
	"postal_code" => "10008",
	"contact_number" => "1-34-9008889",
	"reference" => "18"
	);


$merchandise = Inkmonk_Merchandise::all();
$m1 = $merchandise[15];

var_dump($m1);

$claim = Inkmonk_Claim::create(array(
	"customer" => $new_customer_params,
	"slots" => array(
		array("choices"=> $m1,
			  "quantity"=> 1),
		),
	"form_title" => "Claim Awesome Gifts"
));

var_dump($claim->url);

var_dump($claim->reference);



