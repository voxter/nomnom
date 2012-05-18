#!/usr/bin/php
<?php

global $CDB, $DBNAME;

require_once("CouchDB.php");

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;

$DBNAME = "vqmon";
$EXHANGE_NAME = "vqmonitor";


$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$CDB->send("PUT","/$DBNAME");


function nomnom( $envelope, $queue ) {
	global $CDB, $DBNAME;
	echo __FILE__." consuming...\n";

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
	$resp = $CDB->send("POST","/$DBNAME/",json_encode($cdata));
	echo "Sent to couch $DBNAME \n";
	$CDB->log($resp);
	$queue->ack($envelope->getDeliveryTag());

	return(true);
}



$cnn = new AMQPConnection();

$cnn->setHost('127.0.0.1');

if ($cnn->connect()) {
	echo "Established a connection to AMQP\n";

	$ch = new AMQPChannel($cnn);
	$ex = new AMQPExchange($ch);

	$ex->setName($EXCHANGE_NAME);
	$ex->setType(AMQP_EX_TYPE_FANOUT);
	$ex->setFlags(AMQP_DURABLE);
	$ex->declare();



	$q = new AMQPQueue($ch);
	$q->declare();
	$q->bind($EXCHANGE_NAME,'*'); //USE * FOR ROUTING KEY SO YOU GET EVERYTHING bind("EXCHANGE NAME","ROUTING KEY")
	
	$q->consume('nomnom');

} else {
	echo "Cannot connect to AMQP\n";
}










?>
