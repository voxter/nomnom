#!/usr/bin/php
<?php

require_once("CouchDB.php");
require_once("parse_function.php");

global $CDB;

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;
//$CBOPTS['user'] = 'patch';
//$CBOPTS['pass'] = 'testing';
//$CBOPTS['profile'] = true;
//$CBOPTS['debug'] = true;


$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$resp = $CDB->send("PUT","/vqr");




function nomnom( $envelope, $queue ) {
	global $CDB;
	echo "nomparseconsume...\n";
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
	$parsed_data = parse_sip_vqr($cdata);	
	if( $parsed_data != false ) {
		$resp = $CDB->send("POST","/vqr/",json_encode($parsed_data));
		echo "Sent to couch\n";
		$CDB->log($resp);
	} else {
		$CDB->log("received invalid data!!!!!!!!!!!!!!!!!!!!!!!!!!");
	}	
	$queue->ack($envelope->getDeliveryTag());

	return(true);
}



$cnn = new AMQPConnection();

$cnn->setHost('127.0.0.1');

if ($cnn->connect()) {
	echo "Established a connection to the broker\n";

	$ch = new AMQPChannel($cnn);
	$ex = new AMQPExchange($ch);

	$ex->setName('vqmonitor');
	$ex->setType(AMQP_EX_TYPE_FANOUT);
	$ex->setFlags(AMQP_DURABLE);
	$ex->declare();



	$q = new AMQPQueue($ch);
	//DONT SET A NAME FOR A QUE IF YOU WANT IT TO AUTO GEN A Q NAME
	//$q->setName('');
	//$q->setFlags(AMQP_DURABLE);
	$q->declare();
	$q->bind('vqmonitor','*'); //USE * FOR ROUTING KEY SO YOU GET EVERYTHING bind("EXCHANGE NAME","ROUTING KEY")
	//$ex->bind('vqmonitor','*'); //DONT BIND AN EXCHANGE UNLESS YOU NEED TO BIND IT TO ANOTHER EXCHANGE AND CHANGE IT'S ROUTING KEY
	
	$q->consume('nomnom');

} else {
	echo "Cannot connect to the broker\n";
}










?>
