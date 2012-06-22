<?php



require_once("CouchDB.php");
//require_once("parse_function.2.php");
require_once("parse_function.php");

global $CDB;

$CBOPTS['host'] = "127.0.0.1";
$CBOPTS['port'] = 5984;

$CDB = new CouchDB($CBOPTS); // See if we can make a connection
$testing = $CDB->send("GET","/vqmon/_design/generic/_view/by_callid");

function get_mtr($remote_addr) {

	exec("/usr/sbin/mtr -n -r -c 10 {$remote_addr}",$output);
	$output = str_replace('%','',$output);

	$mtr_entry = array();
	$merge_keys = array();
	foreach( $output as $key => $line ) {
		if( $key == 0 ) {
			$tmp = sscanf($line,"%s\tSnt: %2d\t%s %s %s %s %s %s");
			array_shift($tmp);
			array_shift($tmp);
			$merge_keys = array_merge(array("IP"),$tmp);
		} else {
			$tmp = sscanf($line,"%s %s %s %s %s %s %s");
			$store_this = array_combine($merge_keys,$tmp);
			$mtr_entry[] = $store_this;
		}
	}

	return($mtr_entry);
}




foreach( $testing['rows'] as $row ) {

	
	$cdata = $row['value'];
	//$pd = @parse_sip_vqr($row['value']);
	//$pd = @parse_sip_vqr($row['value']);

	if( isset($cdata['RemoteAddr']) ) {
		$tmp = sscanf($cdata['RemoteAddr'],"IP=%s PORT=%s SSRC=%s");
	}
	print_r($tmp);
	
	$mtr = get_mtr($tmp[0]);
	print_r($mtr);
	/*
	//exec("/usr/sbin/mtr -n -r -c 10 {$tmp[0]}",$output);
	//file_put_contents("/tmp/test.serial", serialize($output));
	$output = unserialize(file_get_contents("/tmp/test.serial"));
	$output = str_replace('%','',$output);
	print_r($output);

	$mtr_entry = array();
	$merge_keys = array();
	foreach( $output as $key => $line ) {
		if( $key == 0 ) {
			$tmp = sscanf($line,"%s\tSnt: %2d\t%s %s %s %s %s %s");
			array_shift($tmp);
			array_shift($tmp);
			$merge_keys = array_merge(array("IP"),$tmp);
		} else {
			$tmp = sscanf($line,"%s %s %s %s %s %s %s");
			$store_this = array_combine($merge_keys,$tmp);
			//print_r($store_this);
			$mtr_entry[] = $store_this;
		}
		// [0] => qoe.mtl1.cloudpbx.ca              Snt: 10    Loss%  Last   Avg  Best  Wrst StDev 

	}
	print_r($mtr_entry);
	*/
	exit(0);




	//print_r($row);
	//$pd = @parse_sip_vqr($row['value']);

}









?>
