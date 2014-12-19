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
	"contact_number" => "1-34-9008889"
	);


$merchandise = Inkmonk_Merchandise::all();
$m1 = $merchandise[0];
$m2 = $merchandise[1];



$claims = Inkmonk_Claim::create(array(
	"customers" => array($new_customer_params ),
	"slots" => array(
		array($m1, 1),
		array($m2->skus, 2)
		),
	"form_title" => "Claim Awesome Gifts"
));


//var_dump($claims);
foreach($claims as $claim){
	var_dump($claim->url);
}


$shipment = Inkmonk_Shipment::get('1');

$merchandise = Inkmonk_Merchandise::all();


var_dump($shipment->address1);

foreach($merchandise as $merch){
	var_dump($merch->name);
}