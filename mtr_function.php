<?php

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

?>
