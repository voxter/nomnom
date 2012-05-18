#!/usr/bin/php
<?php

require_once("Hippy.php");
require_once("parse_function.php");

function nomnom( $envelope, $queue ) {
	global $CDB;
	echo "nomnom alert\n";
	$body = $envelope->getBody();
	$exbody = explode("\n",$body);

	$cdata = array();
	foreach( $exbody as $key => $line ) {
		if( strlen(trim($line)) == 0 ) continue;

		if( $key == 0 ) {
			$cdata['sip_header'] = $line;
		} else {
			$colon = strpos($line,":");
			$nkey = substr($line,0,$colon);
			$nval = trim(substr($line,$colon+1));
			$cdata[$nkey] = $nval;
		}
	}

	echo "Got {$cdata['sip_header']}\n";
	$pd = parse_sip_vqr($cdata);	
	if( $pd != false ) {
		$msg = "MOSLQ=<b>{$pd['QualityEst']['MOSLQ']}</b> MOSCQ=<b>{$pd['QualityEst']['MOSCQ']}</b> CallID=<b>{$pd['CallID']}</b> From=<b>{$pd['From']['sip_address']}</b> To=<b>{$pd['To']['sip_address']}</b> <a href='http://localhost:5984/vqr/_design/generic/_view/by_callid?key=%22{$pd['CallID']}%22'>vqr link</a>";	
		if( $pd['QualityEst']['MOSLQ'] < 4.4 || $pd['QualityEst']['MOSCQ'] < 4.4 ) {
			//Hippy::add("Found MOSLQ score less than 5.0 (testing)");
			Hippy::add("Alert ".$msg);
			Hippy::go();
		} else {
			echo strip_tags($msg)."\n";
		}
	} else {
		$CDB->log("received invalid data!!!!!!!!!!!!!!!!!!!!!!!!!!");
	}	
	$queue->ack($envelope->getDeliveryTag());

	return(true);
}



$cnn = new AMQPConnection();

$cnn->setHost('127.0.0.1');

if ($cnn->connect()) {

	print("NomNomAlert Connected to AMQP vqmonitor exchange.\n");
	Hippy::speak('NomNomAlert Started ');

	$ch = new AMQPChannel($cnn);
	$ex = new AMQPExchange($ch);

	$ex->setName('vqmonitor');
	$ex->setType(AMQP_EX_TYPE_FANOUT);
	$ex->setFlags(AMQP_DURABLE);
	$ex->declare();



	$q = new AMQPQueue($ch);
	$q->declare();
	$q->bind('vqmonitor','*'); //USE * FOR ROUTING KEY SO YOU GET EVERYTHING bind("EXCHANGE NAME","ROUTING KEY")
	Hippy::add('Connected to AMQP vqmonitor exchange.');
	Hippy::go();
	$q->consume('nomnom');

} else {
	echo "Cannot connect to the broker\n";
}










?>
