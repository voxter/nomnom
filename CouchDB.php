<?php



class CouchDB {

	function CouchDB( $options ) {
		$this->force_no_decode = false;	
		$this->debug = false;	

		foreach($options as $key => $value) $this->$key = $value; 
		$auth = array();

	}

	function log( $logthis ) {
		if( $this->debug ) {
			echo $logthis."\n";
		} else {
			file_put_contents("/tmp/testing.log",date("Y-m-d H:i:s")." - ".$logthis."\n",FILE_APPEND);
		}
	}

	function get( $type, $id = null, $filters = array(), $account_id = null ) {

		if( $account_id == null ) $account_id = $this->use_account_id;

		$filter = '';

		if( count($filters) ) {
			foreach( $filters as $key => $val ) $filter .= "filter_$key=$val&";
			if( strlen($filter) ) $filter = '?'.substr($filter,0,-1);
		} else if( strlen($id) ) {
			$filter = "/$id";
		}

		//$this->log("GET /v1/accounts/{$account_id}/$type$filter");
		$response = $this->send("GET","/v1/accounts/{$account_id}/$type$filter");

		return($response['data']);



	}

	function post( $type, $id, $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("POST","/v1/accounts/{$account_id}/$type/$id", json_encode(array('data'=>$data)));
		return($response);
	}

	function put( $type, $data, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("PUT","/v1/accounts/{$account_id}/$type/", json_encode(array('data'=>$data)));
		return($response);
	}


	function del( $type, $id, $account_id = null ) {
		if( $account_id == null ) $account_id = $this->use_account_id;
		$response = $this->send("DELETE","/v1/accounts/{$account_id}/$type/$id");
		return($response);
	}




	
	function send( $method, $url, $post_data = NULL, $type = 'application/json' ) {

		$bldred=chr(0x1B).'[1;31m'; $bldgrn=chr(0x1B).'[1;32m'; $bldylw=chr(0x1B).'[1;33m'; $bldblu=chr(0x1B).'[1;34m'; $bldpur=chr(0x1B).'[1;35m'; $bldcyn=chr(0x1B).'[1;36m'; $bldwht=chr(0x1B).'[1;37m'; $txtrst=chr(0x1B).'[0m'; 


		$mstart = microtime(true);
		$s = fsockopen($this->host, $this->port, $errno, $errstr);
		if(!$s) {
			echo "$errno: $errstr\n";
			return false;
		}

		//$request = "$method $url HTTP/1.1\r\nHost: $this->host:$this->port\r\n";
		$request = "$method $url HTTP/1.0\r\nHost: $this->host\r\n";
		if (isset($this->user)) $request .= "Authorization: Basic ".base64_encode("$this->user:$this->pass")."\r\n";


		$request .= "Content-Type: $type\r\n";
		$request .= "Accept: application/json, application/octet-stream, audio/*\r\n";
		if( isset($this->xauth) ) $request .= "X-Auth-Token: {$this->xauth}\r\n";

		if($post_data) {
			//$request .= "Content-Type: application/json\r\n";
			$request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
			$request .= "$post_data\r\n";
		} else {
			$request .= "\r\n";
		}


		fwrite($s, $request);
		$response = "";

		while(!feof($s)) { $response .= fgets($s); }

		$mend = microtime(true);

		//if( $this->profile ) printf("{$bldblu}URL:{$bldylw}$url {$bldblu}µT:{$bldylw}".( $mend - $mstart ).$txtrst."\n");
		$this->log( "$url µT:".( $mend - $mstart ));

		list($this->headers, $this->body) = explode("\r\n\r\n", $response);

		if( $method == "DELETE" ) {
			if( stristr($this->headers,"204 No Content") ) {
				return( array('status' => 'success'));
			}

		}

		if( !stristr($this->headers,"200 OK") && !stristr($this->headers,"201 Created")) { 

			$this->log("{$bldpur}>>>>: $method $url HTTP/1.0 ($type) POST_LENGTH:".strlen($post_data)."$txtrst \n");
			if( $post_data && $type == 'application/json' ) printf("{$bldpur}POST_DATA: ".trim($post_data)."\n");
			$this->log("{$bldylw}<<<<: µT=".( $mend - $mstart )."{$txtrst}\n");
			$this->log("{$bldylw}<<<<: H:".$this->headers."{$txtrst}");
			$this->log("{$bldylw}<<<<: B:".$this->body."{$txtrst}");


		}
		
		if( $this->force_no_decode ) {

			return $this->body;
		} 

		return json_decode($this->body,true);
		

	}



}



?>
