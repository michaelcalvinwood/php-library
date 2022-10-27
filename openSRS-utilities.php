<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

function openSRS_parseResponse ($result) {
	$response = trim($result);
	
	$response = str_replace ('{', "", $response);
	$response = str_replace ('}', "", $response);
	
	$pairs = explode (',', $response);
	
	$num = count ($pairs);
	
	$reply = array();
	
	for ($i = 0; $i < $num; ++$i) {
		$value = explode (':', $pairs[$i]);
		if (count($value) === 2) {
			$value[0] = str_replace ('"', "", $value[0]);
			$value[1] = str_replace ('"', "", $value[1]);
			$reply[trim($value[0])] = trim($value[1]);
		}
	}

	$count = count($reply);
	
	if ($count === 0) {
		return false;
	}
	
	return $reply;
}

function openSRS_emailAPI ($method, $input)
{

	$key = '';
	$user = "";

	$request = array();
	$request = $input;
	
	$request['credentials'] = array();
	$request['credentials']['user'] = "";
	$request['credentials']['password'] = '';
	
	//print_r($request);

	$data_string = json_encode($request);

	//sendAlert ($data_string);
	
	$url = 'https://admin.b.hostedemail.com/api/';

	$ch=curl_init($url.$method);

	//curl_setopt ($ch, CURLINFO_HEADER_OUT, true);
	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    	'Content-Type: application/json',
    	'Content-Length: ' . strlen($data_string))
		);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

	$result = curl_exec($ch);
	//+43w $headerSent = curl_getinfo ($ch, CURLINFO_HEADER_OUT);
	//sendAlert ("headersent\n".$headerSent);
	
	
	curl_close($ch);
	
	return json_decode($result, true);
}
	
function openSRS_emailRequest ($method, $input)
{

	$key = '';
	$user = "";

	$request = array();
	$request = $input;
	
	$request['credentials'] = array();
	$request['credentials']['user'] = "";
	$request['credentials']['password'] = '';
	
	//print_r($request);

	$data_string = json_encode($request);

	//sendAlert ($data_string);
	
	$url = 'https://admin.b.hostedemail.com/api/';

	$ch=curl_init($url.$method);

	//curl_setopt ($ch, CURLINFO_HEADER_OUT, true);
	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    	'Content-Type: application/json',
    	'Content-Length: ' . strlen($data_string))
		);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

	$result = curl_exec($ch);
	//+43w $headerSent = curl_getinfo ($ch, CURLINFO_HEADER_OUT);
	//sendAlert ("headersent\n".$headerSent);
	
	
	curl_close($ch);
	
	return openSRS_parseResponse($result);
}
	

function openSRS_createEmailAccount ($name, $email, $password)
{
	$r = array();
	
	$r['user'] = $email;
	
	$r['attributes'] = array();
	$r['attributes']['name'] = $name;
	$r['attributes']['password'] = $password;
	$r['attributes']['reject_spam'] = false;
	$r['attributes']['service_imap4'] = 'enabled';
	$r['attributes']['service_pop3'] = 'enabled';
	$r['attributes']['service_smtpin'] = 'enabled';
	//$r['attributes']['smtp_sent_limit'] = $sendLimit;
	$r['attributes']['spamfolder'] = 'Spam';
	
	
	$r['create_only'] = true;
	
	$result = openSRS_emailRequest('change_user', $r);
	
	return $result;
}

//$result = openSRS_createEmailAccount('Test Account', 'test2@phixmail.com', 'simple123', 150);
/*
$result = openSRS_parseResponse('{"success":true,"job":"1523482169166", "audit":"son91_5ace7e3813"}');
require_once ("terraSecurity.php");
printWell ($result);
*/

?>