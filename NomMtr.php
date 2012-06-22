#!/usr/bin/php
<?php

global $CDB;

require_once("CouchDB.php");
require_once("Hippy.php");
require_once("parse_function.php");
require_once("mtr_function.php");

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;

$EXCHANGE_NAME = "vqmonitor";


$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$CDB->send("PUT","/vqmtr");


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

	$pd = parse_sip_vqr($cdata);	
	if( $pd != false ) {
	


		$duration = strtotime($pd['STOP']) - strtotime($pd['START']);
		$msg = "Duration: <b>$duration</b> MOSLQ=<b>{$pd['QualityEst']['MOSLQ']}</b> MOSCQ=<b>{$pd['QualityEst']['MOSCQ']}</b> CallID=<b>{$pd['CallID']}</b> From=<b>{$pd['From']['sip_address']}</b> To=<b>{$pd['To']['sip_address']}</b> <a href='http://localhost:5984/vqr/_design/generic/_view/by_callid?key=%22{$pd['CallID']}%22'>vqr link</a>";	
		//if( $pd['QualityEst']['MOSLQ'] < 4.4 && $duration > 10 ) {
		if( 1 ) {
			
			//Hippy::add("Alert ".$msg);
			//Hippy::go();
		
			$remote_addr = null;
			if( isset($cdata['RemoteAddr']) ) {
				$tmp = sscanf($cdata['RemoteAddr'],"IP=%s PORT=%s SSRC=%s");
				$remote_addr = $tmp[0];
			}
			
			$mtr = get_mtr($remote_addr);
			$mtr['id'] = $cdata['CallID'];

			$CDB->send("PUT","/vqmtr/{$cdata['CallID']}",json_encode($mtr));
			echo strip_tags($msg)."\n";
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

Hippy::speak('NomMtr Consumer Started '.basename(__FILE__));
if ($cnn->connect()) {
	echo "Established a connection to AMQP (MTR)\n";
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
