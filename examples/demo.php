<?php

include('../inkmonk.php');

Inkmonk::init();

$shipment = Shipment::get('1');

$merchandise = Merchandise::all();


var_dump($shipment->address1);

foreach($merchandise as $merch){
	var_dump($merch->name);
}