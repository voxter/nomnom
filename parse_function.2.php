<?php

function parse_sip($ToID) {

	$filter = array('<','>','sip:','sips:');
	$ToID = str_replace($filter,'',$ToID);

	//echo $ToID."\n";

	$tmp1 = sscanf($ToID,'"%[^"]" %s');
	$tmp2 = sscanf($ToID,'%[^;];%s');
	$tmp3 = sscanf($ToID,'%s');

	$test = strlen($tmp1[0]) + strlen($tmp2[0]) + strlen($tmp3[0]);
	if( $test == 0 ) echo $ToID."\n";

	if( is_array($tmp1) ) $tmp1 = array_reverse($tmp1);
	
	if( strlen($tmp1[0]) ) return($tmp1);
	if( strlen($tmp2[0]) ) return($tmp2);
	if( strlen($tmp3[0]) ) return($tmp3);

}

function is_outbound($ToID) {
	if( stristr($ToID, "user=phone") ) return(true);
	return(false);

}






function explodex($in,$prefix='') {

	$out = array();
	$tmp = explode(' ',$in);

	foreach( $tmp as $x ) {
		$tmp2 = explode('=',$x);
		$out[$prefix.$tmp2[0]] = $tmp2[1];
	}
	return($out);
}


function parse_sip_vqr($row) {

	$aout = array();
	if( $row['Content-Type'] == "application/vq-rtcpxr" ) {

		$aout['CallID'] = $row['CallID'];
		$aout['Call-ID'] = $row['Call-ID'];
		$aout['User-Agent'] = $row['User-Agent'];		
		
		$aout['ReportIP'] = $row['ReportIP'];

		$tmp = explode(":",$row['FromID']);
		$FromPort = 0;
		if( isset($tmp[2]) ) {
			$tmp2 = explode(";",$tmp[2]);
			$FromPort = $tmp2[0];
		} 

		$from_id_parsed = parse_sip($row['FromID']);
		$to_id_parsed = parse_sip($row['ToID']);
		$to_parsed = parse_sip($row['To']);




		//The following logic will make your head hurt but it works.

		if( is_outbound($row['ToID']) ) {
			//printf("%40s\t%40s\n",$from_id_parsed[0],$to_id_parsed[0]);
			$aout['From']['sip_address'] = $from_id_parsed[0];
			$aout['To']['sip_address'] = $to_id_parsed[0];
		} else {
			//printf("%40s\t%40s\n",$to_id_parsed[0],$to_parsed[0]);
			$aout['From']['sip_address'] = $to_id_parsed[0];
			$aout['To']['sip_address'] = $to_parsed[0];
		}
		
		$tmp = explode("@", $to_parsed[0] );
		$aout['Realm'] = $tmp[1];

		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $row['FromID'], $matches);
		$tmp = explode("@",$matches[0][0]);
		$aout['From']['username'] = $tmp[0];
		$aout['From']['ip'] = $tmp[1];
		$aout['From']['port'] = $FromPort;
		
		if( stristr($row['FromID'], "tcp") ) $aout['From']['proto'] = 'tcp';
		if( stristr($row['FromID'], "udp") ) $aout['From']['proto'] = 'udp';

		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $row['ToID'], $matches);
		$tmp = explode("@",$matches[0][0]);
		$aout['To']['username'] = $tmp[0];
		$aout['To']['ip'] = $tmp[1];
		

		$tmp = explodex($row['Timestamps']);
		$aout = array_merge($aout,$tmp);


		//$aout['Session']
		//$tmp = explode("=",$row['SessionDesc']);
		$tmp = sscanf($row['SessionDesc'], 'PT=%d PD="%[^"]" SR=%d PPS=%d PLC=%d SSUP=%s');

		$Session['PT'] = $tmp[0];
		$Session['PD'] = $tmp[1];
		$Session['SR'] = $tmp[2];
		$Session['PPS'] = $tmp[3];
		$Session['PLC'] = $tmp[4];
		$Session['SSUP'] = $tmp[5];

		$aout['Session'] = $Session;
		

		$aout['Local'] = explodex($row['LocalAddr']);
		$aout['Remote'] = explodex($row['RemoteAddr']);
		$aout['QualityEst'] = explodex($row['QualityEst']);
		$aout['JitterBuffer'] = explodex($row['JitterBuffer']);
		$aout['PacketLoss'] = explodex($row['PacketLoss']);
		$aout['BurstGapLoss'] = explodex($row['BurstGapLoss']);
		$aout['Signal'] = explodex($row['Signal']);
		$aout['Delay'] = explodex($row['Delay']);
		//$aout['SessionDesc'] = $row['SessionDesc'];
         


		//This is really really lazy
		if( !stristr($aout['From']['sip_address'],$aout['From']['username']) ) {
			$tmp = 	$aout['From']['sip_address'];
			$aout['From']['sip_address'] = $aout['To']['sip_address'];
			$aout['To']['sip_address'] = $tmp;	
		}



		return($aout);
	} 


	return(false);

}




?>
