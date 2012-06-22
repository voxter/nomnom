<?php



require_once("CouchDB.php");
//require_once("parse_function.2.php");
require_once("parse_function.php");

global $CDB;

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;

$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$testing = $CDB->send("GET","/vqmon/_design/generic/_view/by_callid");


foreach( $testing['rows'] as $row ) {
	//print_r($row);
	$pd = @parse_sip_vqr($row['value']);
	if( isset($pd['From']) && !stristr($pd['From']['sip_address'], $pd['From']['username']) ) {
		//print_r($pd['From']);
		//print_r($pd['To']);

		//This is really really lazy
		if( !stristr($pd['From']['sip_address'],$pd['From']['username']) ) {
			$tmp = 	$pd['From']['sip_address'];
			$pd['From']['sip_address'] = $pd['To']['sip_address'];
			$pd['To']['sip_address'] = $tmp;	
		}

		

		$resp = $CDB->send("PUT","/vqr/{$row['id']}",json_encode($pd));
		
		echo "/vqr/{$row['id']}\n";
		print_r($resp);
	}

	



}











?>
