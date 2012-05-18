#!/usr/bin/php
<?php

global $CDB;

require_once("CouchDB.php");
require_once("Hippy.php");
require_once("parse_function.php");

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;

$EXCHANGE_NAME = "vqmonitor";


$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$CDB->send("PUT","/vqmon");
$CDB->send("PUT","/vqr");


function nomnom( $envelope, $queue ) {
	global $CDB;
	echo __FILE__." ".__FUNCTION__." consuming...\n";

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
	$CDB->send("POST","/vqmon/",json_encode($cdata));
	echo "Sent to couch vqmon\n";

	$pd = parse_sip_vqr($cdata);	
	if( $pd != false ) {
		$CDB->send("POST","/vqr/",json_encode($pd));
		echo "Sent to couch vqr\n";

		$msg = "MOSLQ=<b>{$pd['QualityEst']['MOSLQ']}</b> MOSCQ=<b>{$pd['QualityEst']['MOSCQ']}</b> CallID=<b>{$pd['CallID']}</b> From=<b>{$pd['From']['sip_address']}</b> To=<b>{$pd['To']['sip_address']}</b> <a href='http://localhost:5984/vqr/_design/generic/_view/by_callid?key=%22{$pd['CallID']}%22'>vqr link</a>";	
		if( $pd['QualityEst']['MOSLQ'] < 4.4 || $pd['QualityEst']['MOSCQ'] < 4.4 ) {
			Hippy::add("Alert ".$msg);
			Hippy::go();
		} else {
			echo strip_tags($msg)."\n";
		}

	} else {
		$CDB->log("received invalid data!!!!!!!!!!!!!!!!!!!!!!!!!!");
	}

	//$CDB->log($resp);
	$queue->ack($envelope->getDeliveryTag());

	return(true);
}



$cnn = new AMQPConnection();

$cnn->setHost('127.0.0.1');

Hippy::speak('NomNom Consumer Started '.basename(__FILE__));
if ($cnn->connect()) {
	echo "Established a connection to AMQP\n";
	Hippy::add("Connected to AMQP $EXCHANGE_NAME exchange.");

	$ch = new AMQPChannel($cnn);
	$ex = new AMQPExchange($ch);

	$ex->setName($EXCHANGE_NAME);
	$ex->setType(AMQP_EX_TYPE_FANOUT);
	$ex->setFlags(AMQP_DURABLE);
	$ex->declare();



	$q = new AMQPQueue($ch);
	$q->declare();
	$q->bind($EXCHANGE_NAME,'*'); //USE * FOR ROUTING KEY SO YOU GET EVERYTHING bind("EXCHANGE NAME","ROUTING KEY")
	Hippy::go();

	$q->consume('nomnom');

} else {
	Hippy::add("Cannot connect to AMQP $EXCHANGE_NAME exchange.");
	Hippy::go();
}










?>
