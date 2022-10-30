<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

require 'vendor/autoload.php';
require_once ("terraSecurity.php");
require_once ("randStrGen.php");
require_once ("db-conx.php");

/*
secureSessionContinue('<br>Please login at <a href="https://phishviewer.com" style="color:blue; text-decoration: underline;">PhishViewer.com</a>.');
session_write_close();
*/

function fetchSentFolder ($email) {
	$r = getSubfolders($email, "");

	$count = count($r);

	$sentFolder = "";

	for ($i = 0; $i < $count; ++$i) {
		$r[$i] = stripQuotes($r[$i]);
		$test = strtolower($r[$i]);

		if (strncmp($test, 'sent', 4) === 0) {
			$parts = explode('~', $r[$i]);
			$sentFolder = $parts[0];
			break;
		} 
	}
	if (strlen($sentFolder) === 0) return false;
	
	return $sentFolder;
}

function getSubfolders ($email, $folder) {
	
	// returns an array in the following format: folderName~[y/n]
	//    ~y : has children
	//    ~n : does not have children
	//    In the future escape folder names that contain tildas
	
	$command = 'LIST "'. $folder. '" "%"';

	$db = connectDatabase();
	$imap = getIMAPCredentials($db, $email);
	mysqli_close($db);

	$fp = loginEmail ($imap['account'], $imap['password'], $imap['host'], $imap['port'], $imap['proto']);
	
	$result = sendCommand ($fp, $command);

	softLogoutEmail ($fp);

	$lines = explode("\n", $result);

	$num = count($lines);

	$tmpFolders = array();
	$numFolders = 0;
	
	for ($i = 0; $i < $num; ++$i) {
		$lines[$i] = trim($lines[$i]);
		if (strncmp ($lines[$i], '*', 1) === 0) {
			$value = parsePart ($lines[$i]);

			$count = count($value);
			$tmpFolders[$numFolders] = '"' . str_replace('"', '', $value[4]) . '"';
			if (preg_match('/\(.*\\HasChildren.*\)/i', $lines[$i])) {
				$tmpFolders[$numFolders] .= '~y';
			} else {
				$tmpFolders[$numFolders] .= '~n';
			}
			++$numFolders;
		}
		
	}	
	
	return $tmpFolders;
}

function getSentFolder ($d, $e) {
	$q = "SELECT settings FROM imapServers WHERE email = " . q($e);
	
	$r = mysqli_query ($d, $q);

	if ($r === false) {
		mysqli_close ($d);
		return false;
	}

	$numRows = mysqli_num_rows($r);

	if ($numRows === 0) {
		mysqli_close ($d);
		return false;
	}

	$row = mysqli_fetch_row($r);
	
	$c = json_decode($row[0], true);
	print_r($c);
	
	if (isset($c['sent'])) {
		return $c['sent'];
	}
	
	return 'Sent';
}

function insertIMAP ($db, $email, $account, $password, $host, $port, $proto, $settings){
	
	$secretKey = "keiIKE#@kdU#83udl38lsdEIljEy3d";
	
	$iv = getIv();
	
	$password = urlencode(encryptStr($password, $secretKey, $iv));
	$iv = urlencode(base64_encode($iv));
	
	$entry = array ();
	
	$entry['email'] = $email;
	$entry['account'] = $account;
	$entry['password'] = $password;
	$entry['host'] = $host;
	$entry['port'] = $port;
	$entry['proto'] = $proto;
	$entry['iv'] = $iv;
	$entry['settings'] = $settings;
	
	insertIntoTable($db, 'imapServers', $entry);
	
	if (strlen($settings) > 0) {
		$s = json_decode($settings, true);
	} else {
		$s = array();
	}
	
	$s['sent'] = fetchSentFolder($email);
	$settings = jEncode($s);
	
	$q = "UPDATE imapServers SET settings = " . q($settings) . 'WHERE email = ' . q($email);
	$r = mysqli_query ($db, $q);
	
}

function insertSMTP ($db, $email, $account, $password, $host, $port, $proto, $settings) {
	$secretKey = "keiIKE#@kdU#83udl38lsdEIljEy3d";
	
	$iv = getIv();
	
	$password = urlencode(encryptStr($password, $secretKey, $iv));
	$iv = urlencode(base64_encode($iv));

	$entry = array ();
	
	$entry['email'] = $email;
	$entry['account'] = $account;
	$entry['password'] = $password;
	$entry['host'] = $host;
	$entry['port'] = $port;
	$entry['proto'] = $proto;
	$entry['iv'] = $iv;
	$entry['settings'] = $settings;
	
	insertIntoTable($db, 'smtpServers', $entry);
	
}

function getIMAPCredentials ($db, $email) {
	$secretKey = "keiIKE#@kdU#83udl38lsdEIljEy3d";
	
	$query = 'SELECT account, password, host, port, proto, iv FROM imapServers WHERE email = ' . q($email);
	
	$result = mysqli_query ($db, $query);
	
	if ($result === false) {
		return false;
	}
	
	$numRows = mysqli_num_rows($result);
	
	if ($numRows < 1) {
		return false;
	}
	
	$row = mysqli_fetch_row ($result);
	
	$c = array ();
	
	$c['account'] = $row[0];
	$password = urldecode($row[1]);
	$c['host'] = $row[2];
	$c['port'] = $row[3];
	$c['proto'] = $row[4];
	$iv = base64_decode(urldecode($row[5]));
	
	$c['password'] = decryptStr ($password, $secretKey, $iv);

	
	mysqli_free_result ($result);

	/*
	$c['host'] = '10.156.5.135';
	$c['port'] = 5000;
	$c['proto'] = 'ssl';
	*/
	return $c;
}

function getSMTPCredentials ($db, $email) {
	$secretKey = "keiIKE#@kdU#83udl38lsdEIljEy3d";
	
	$query = 'SELECT account, password, host, port, proto, iv FROM smtpServers WHERE email = ' . q($email);
	
	$result = mysqli_query ($db, $query);
	
	if ($result === false) {
		return false;
	}
	
	$numRows = mysqli_num_rows($result);
	
	if ($numRows < 1) {
		return false;
	}
	
	$row = mysqli_fetch_row ($result);
	
	$c = array ();
	
	$c['account'] = $row[0];
	$password = urldecode($row[1]);
	$c['host'] = $row[2];
	$c['port'] = $row[3];
	$c['proto'] = $row[4];
	$iv = base64_decode(urldecode($row[5]));
	
	$c['password'] = decryptStr ($password, $secretKey, $iv);
	
	return $c;
}



function parseAddrs ($addr) {
	
	if (strlen ($addr) === 0) {
		return false;
	}
	
	$to = array();
	
	if (strlen ($addr) === 0) {
		return $to;
	}
	
	$target = array();
	
	$target[] = '<';
	$target[] = '>';
	$target[] = ',';
	$target[] = ';';
	$target[] = '"';
	$target[] = "'";
	$target[] = '&lt;';
	$target[] = '&gt;';
	$target[] = "\r";
	$target[] = "\n";
	
	$refAddr = str_replace($target, " ", $addr);
		
	$value = explode (' ', $refAddr);
	
	$numParts = count($value);
	
	if ($numParts === 0) {
		return false;
	}
	
	$curPair = 0;
	
	$to[$curPair] = array();
	$to[$curPair]['name'] = "";
	$to[$curPair]['addr'] = "";
	
	for ($i = 0; $i < $numParts; ++$i) {
		
		if (strlen($value[$i]) === 0) {
			continue;
		}
		
		if (strpos ($value[$i], '@') !== false) {
			$to[$curPair]['addr'] = $value[$i];
			
			if ($i === ($numParts - 1)) {
				break;
			}
			
			++$curPair;
			$to[$curPair] = array();
			$to[$curPair]['name'] = "";
			$to[$curPair]['addr'] = "";
		} else {
			if (strlen ($to[$curPair]['name']) === 0) {
				$to[$curPair]['name'] = $value[$i]; 
			} else {
				$to[$curPair]['name'] .= ' '.$value[$i];
			}
		}
	}
	
	if (strlen($to[$curPair]['addr']) === 0) {
		unset($to[$curPair]);
	}
	
	$numTo = count($to);

	if ($numTo === 0) {
		return false;
	}
	
	$send[0] = $to[0];
	
	// remove possible duplicates
	
	if ($numTo > 1) {
		
		for ($i = 1; $i < $numTo; ++$i) {
			$flagExists = false;
			$numSend = count($send);
			
			for ($ii = 0; $ii < $numSend; ++$ii) {
				if (strcmp($to[$i]['addr'], $send[$ii]['addr']) === 0) {
					$flagExists = true;
					//sendAlert ($to[$i]['addr']." & ". $send[$ii]['addr']);
					break;
				}
			}
			
			if ($flagExists) {
				continue;
			}
			
			$send[$numSend] = $to[$i];			
		}
	}
	
	// insert addresses in autocomplete
	
	$numAddrs = count ($send);
	
	$id = $GLOBALS['id'];
	
	$db = connectDatabase();
	
	for ($i = 0; $i < $numAddrs; ++$i) {
		$name = trim($send[$i]['name']);
		$addr = trim($send[$i]['addr']);
		
		if (strlen ($name) === 0) {
			$name = '~';
		}
		
		if (strlen ($addr) === 0) {
			$addr = '~';
		}
		
		$command = "INSERT INTO autocomplete (id, name, addr) VALUES (" . q($id) . ", " . q($name) . ", " . q($addr) . ")";
		mysqli_query ($db, $command);
		
	}
	
	mysqli_close ($db);
	//sendAlert ("error: ".json_encode ($send));
			
	return ($send);		
}




function getImapSettings () {
	$imap = array();
	
	//$imap['host'] = "mail.b.hostedemail.com";
	$imap['host'] = "mail.phixmail.com";
	$imap['port'] = 993;
	$imap['proto'] = 'ssl';

	return $imap;
}

function sendCommandLimit ($fp, $command, $limit) {
	//$response = sendCommandDebug ($fp, $command); return $response;
	
	$max = intval($limit);
	
	$id = "C".randStrGen(10);
    $response = "";
	
	$command = "$id $command"."\r\n";
	
    fputs ($fp, $command);
	
	while ($line = fgets($fp, $max)) {
		$max -= strlen($line);
		if ($max < 1) {
			sendAlert ("Error: IMAP command ($command) exceeds limit ($limit)");
		}
    	$response = $response.$line;
		$testLine = strtoupper($line);
		if (strpos ($testLine, $id) !== false) {break;}
	}
    
	
	
	//echo "Received: ".$response."<br />";
	
	return $response;
}



function sendCommand ($fp, $command, $option = false) {
	//$response = sendCommandDebug ($fp, $command); return $response;
	
	$total = strlen($command);
	
	$limit = 1024;
	
	$id = "C".randStrGen(10);
    $response = "";
	
	$command = "$id $command"."\r\n";
	
	fj ($command);
	
    fputs ($fp, $command);
	
	while ($line = fgets($fp, $limit)) {
		$total += strlen($line);
		
		if ($total > 36700160) break;
		
		$testLine = strtoupper($line);
		if (strpos ($testLine, $id) === false) {
			$response = $response.$line;
		} else {
			if ($option == "skipStatus") {
				break;
			} else {
				$response = $response.$line;
				break;
			}
		}
	}
	
	if (isset($GLOBALS['email'])) updateQuotaGB ($GLOBALS['email'], $total);
	
	if ($option) {
		switch ($option) {
			case 'alert':
				sendAlert ($command."\n".$response, "sendCommand Alert:");
				break;
		}
	}
	
	fj (substr($response, 0, 256));
	
	return $response;
}

function fetchPart ($fp, $command, $encoding, $size, $feedback = false) {
	$id = "C".randStrGen(10);
	
	$command = "$id $command\r\n";
	
    fwrite($fp, $command);
	
	$response = "";
	
	// skip first line
	
	$firstLine = fgets($fp);
	if (stripos ($firstLine, $id) !== false) {
		if ($feedback) {
			return false;
		} else {
			return $firstLine;
		}
	}
	
	$lastLine = "";
	
	
	while ($line = fgets($fp)) {
		if (stripos ($line, $id) !== false) {break;}
		
		
		switch ($encoding) {
			case 'quoted-printable':
				$line = trim($line);
				if (strcmp(substr($line,-1),'=') === 0) {
					$lineContinue = true;
				} else {
					$lineContinue = false;
				}
				$line = quoted_printable_decode ($line);
				if (!$lineContinue) {
					$line .= "\n";
				}
				break;
			
			case 'binary':
			case '7bit':
			case '8bit':
				break;	
		
			case 'base64':
				$line = trim($line);
				$line = base64_decode ($line);
				break;
			
			default:
				//debug only
				
				/*sendAlert ("Selected email has an unknown encoding: ".$encoding.".", "fetchPart Alert:");
				exit ();
				*/
		}
		
		$response .= $lastLine;
		$lastLine = $line;
	}
	
	return $response;
}


function imapSecureConnect ($host, $port, $proto = 'ssl', $sendAlert = true) {
	
	$prefix = $proto.'://';
	
	$fp = fsockopen($prefix.$host, $port, $errno, $errstr, 15);
	
	
	if ($fp === false) {
		if ($sendAlert) {
			sendAlert ("Error: Could not connect to $origHost via port $port using protocol $proto.", "imapSecureConnect Alert 1:");	
		} else {
			return false;
		}
	}
	
	if (!stream_set_timeout($fp, 15)) {
		if ($sendAlert) {
			sendAlert ("Error: Timeout when connecting to $host:$port.", "imapSecureConnect Alert 2:");
		} else {
			return false;
		}
	}
        
	//$line = fgets($fp);
    
	return $fp;
}

function loginEmailGeneric ($email, $password, $host, $port, $proto) {
	
	$fp = imapSecureConnect ($host, $port, $proto);
	
	// add check for $credentials['accessType'] here

	$result = sendCommand ($fp, 'LOGIN "'.$email.'" "'.$password.'"');

	$result = stripos ($result, " OK ");

	if ($result === false) {
		sendAlert ("Could not access $email using password: $password.\n\nIf you are sure that this is the correct password, check to see if $email requires you to allow third-party IMAP access.  Once you setup third-party IMAP access, you can try the migration again.", "loginEmail Alert:");
	}

	return $fp;
}

function checkPhixmailLogin ($email, $password) {
	
	$imap = getImapSettings();
	
	$fp = imapSecureConnect ($imap['host'], $imap['port'], $imap['proto'], false);
	
	if ($fp === false) {
		return false;
	}

	$result = sendCommand ($fp, 'LOGIN "'.$email.'" "'.$password.'"');

	$result = stripos ($result, " OK ");

	logoutEmail ($fp);

	if ($result === false) {
		return false;
	}
	
	return true;
}


function loginEmail ($email, $password, $host, $port, $proto) {
	
	//echo "$proto:$host:$port<br>\n";
	
	$fp = imapSecureConnect ($host, $port, $proto);
	
	// add check for $credentials['accessType'] here

	$c =  'LOGIN "'.$email.'" "'.$password.'"';
	
	//echo $c."<br>\n";
	
	$result = sendCommand ($fp, $c);
	
	$result = stripos ($result, " OK ");

	$count = 0;
	
	if ($result === false) {
		while ($count < 3) {
			
			sleep (3);
			
			$result = sendCommand ($fp, 'LOGIN "'.$email.'" "'.$password.'"');

			$result = stripos ($result, " OK ");
			
			if ($result !== false) { break; }
			
			++$count;
		}
		
		if ($count >= 3) {
			sendAlert ("Could not access $email using password.\n\nIf you are sure that this is the correct password, check to see if $email account requires you to enable third-party IMAP access.  Once you setup third-party IMAP access, you can try again.", "loginEmail Alert 2:");
		}

	}

	return $fp;
}

function selectFolder ($fp, $folder) {
	
	$command = "SELECT ".$folder;
	
	$result = sendCommand($fp, "SELECT ".$folder);

	$pattern = "~(\d+)\sEXISTS~";
	if (preg_match ($pattern, $result, $match)) {
		$numEmails = $match[1];
		return $numEmails;
	}

	return (false);
}

function fetchBody ($fp, $uid, $partNum, $encoding, $size, $type = "html") {
	
	//sendAlert ($uid);
	
	//IMPORTANT:
	// write a separate function prefetchBody which uses BODY.PEEK for caching purposes
/*	
	if ($prefetch) {
		$command = "UID FETCH ".$uid." (BODY.PEEK[$partNum])";
	} else {
		$command = "UID FETCH ".$uid." (BODY[$partNum])";
	}
*/
	//sendAlert ($command);
	
	$email= array();
	
	$command = "UID FETCH ".$uid." (BODY[$partNum])";
	
	$page = fetchPart ($fp, $command, $encoding, $size, true);
	
	if ($page === false) {
		return false;
	}
	if (strcmp ($type, "html") === 0) {
	
		$dom = new IvoPetkov\HTML5DOMDocument();

		$dom->loadHTML($page);

		$dom->insertHTML('
			<html>
				<head>
					
					<link rel="stylesheet" type="text/css" href="https://phishviewer.com/css/security.css">
					<script src="https://phishviewer.com/js/security.js"></script>
					</head>
					
				<body>
					
					
				</body>
			</html>
		');
		
		$email['all'] =  $dom->saveHTML ();
		
		return $email;
	} else {
		$page = str_replace('>', '&gt;', $page);
		$page = str_replace('<', '&lt;', $page);
	
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		if(preg_match($reg_exUrl, $page, $url)) {
       		// make the urls hyper links
       		$page = preg_replace($reg_exUrl, '<a href="'.$url[0].'" rel="nofollow">'.$url[0].'</a>', $page);
		} 
		
		$email['all'] = '
			<html>
				<head>
				
					<link rel="stylesheet" type="text/css" href="https://phishviewer.com/css/security.css">
					<script src="https://phishviewer.com/js/security.js"></script>
					</head>
					</head>
				
				<body>
					' .
				 	nl2br($page) .
		'		</body>
			</html>';
	}
	
	//$email['body'] = nl2br($page);
	
	return $email;
}

function fetchBaseBody ($fp, $uid, $partNum, $encoding, $size, $base, $type = "html") {
	
	//sendAlert ($uid);
	
	//IMPORTANT:
	// write a separate function prefetchBody which uses BODY.PEEK for caching purposes
/*	
	if ($prefetch) {
		$command = "UID FETCH ".$uid." (BODY.PEEK[$partNum])";
	} else {
		$command = "UID FETCH ".$uid." (BODY[$partNum])";
	}
*/
	//sendAlert ($command);
	
	$email= array();
	
	$command = "UID FETCH ".$uid." (BODY[$partNum])";
	
	$page = fetchPart ($fp, $command, $encoding, $size, true);
	
	if ($page === false) {
		return false;
	}
	if (strcmp ($type, "html") === 0) {
	
		$dom = new IvoPetkov\HTML5DOMDocument();

		$dom->loadHTML($page);

		$dom->insertHTML('
			<html>
				<head>	
					<link rel="stylesheet" type="text/css" href="' . $base . '/css/security.css">
					<script src="' . $base . '/js/security.js"></script>
				</head>
					
				<body>
					
					
				</body>
			</html>
		');
		
		$email['all'] =  $dom->saveHTML ();
		
		return $email;
	} else {
		$page = str_replace('>', '&gt;', $page);
		$page = str_replace('<', '&lt;', $page);
	
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		if(preg_match($reg_exUrl, $page, $url)) {
       		// make the urls hyper links
       		$page = preg_replace($reg_exUrl, '<a href="'.$url[0].'" rel="nofollow">'.$url[0].'</a>', $page);
		} 
		
		$email['all'] = '
			<html>
				<head>	
					<link rel="stylesheet" type="text/css" href="' . $base . '/css/security.css">
					<script src="' . $base . '/js/security.js"></script>
				</head>
					
				
				<body>
					' .
				 	nl2br($page) .
		'		</body>
			</html>';
	}
	
	//$email['body'] = nl2br($page);
	
	return $email;
}


function calcRange ($numEmails, $pageNum, $pageSize) {
	if ($numEmails <= 0) { return false; }
	
	$maxPages = ceil($numEmails/$pageSize);
	
	if ($pageNum > $maxPages) { return false; }
	
	if ($pageNum < 1) { return false; }
	
	$range = array();
	
	$range['last'] = $numEmails-(($pageNum-1)*$pageSize);
	if ($range['last'] < 1) { $range['last'] = $numEmails; }
		
	$range['first'] = $range['last'] - ($pageSize - 1);
	if ($range['first'] < 1) { $range['first'] = 1; }
	
	return $range;
}

function fetchDateRange ($fp, $first, $last, $option = 'seq') {
	switch ($option) {
		case 'seq':
			$query = "FETCH $first:$last (UID BODY.PEEK[HEADER.FIELDS (DATE)])";
			break;
		
		case 'uid':
			$query = "UID FETCH $first:$last (UID BODY.PEEK[HEADER.FIELDS (DATE)])";
			break;
	}
	
	$response = sendCommand($fp, $query, "skipStatus");
	
	$results = explode("\n*", $response);
	
	return $results;
}

function fetchHeaderRange ($fp, $first, $last, $option = 'seq') {
	
	switch ($option) {
		case 'seq':
			$query = "FETCH $first:$last (UID BODY.PEEK[HEADER.FIELDS (DATE FROM TO CC SUBJECT MESSAGE-ID REFERENCES)] FLAGS BODYSTRUCTURE)";
			break;
		
		case 'uid':
			$query = "UID FETCH $first:$last (UID BODY.PEEK[HEADER.FIELDS (DATE FROM TO CC SUBJECT MESSAGE-ID REFERENCES)] FLAGS BODYSTRUCTURE)";
			break;
	}
	
	$response = sendCommand($fp, $query, "skipStatus");
	
	$results = explode("\n*", $response);
	
	return $results;
}

function fetchUidSequence ($fp, $sequence) {
	$query = "UID FETCH $sequence (UID BODY.PEEK[HEADER.FIELDS (DATE FROM TO CC SUBJECT MESSAGE-ID REFERENCES)] FLAGS BODYSTRUCTURE)";
	
	$response = sendCommand($fp, $query, "skipStatus");
	
	$results = explode("\n*", $response);
	
	return $results;
}

function fetchNewerUids ($fp, $uid) {
	$query = "UID FETCH $uid:* (UID BODY.PEEK[HEADER.FIELDS (DATE FROM TO CC SUBJECT MESSAGE-ID REFERENCES)] FLAGS BODYSTRUCTURE)";
	
	$response = sendCommand($fp, $query, "skipStatus");
	
	$results = explode("\n*", $response);
	
	return $results;
}

function getSeqFromUid ($fp, $uid) {
	$query = "UID FETCH $uid FLAGS";
	
	$response = sendCommand ($fp, $query);
	
	$test = preg_match ('~\* (\d+) FETCH~', $response, $match);
	
	if ($test) {
		return(intval($match[1]));	
	}
	
	// this means that the UID doesn't exist
	
	return false;
	
	
	
}

function softLogoutEmail ($fp) {
	$result = sendCommand($fp, "LOGOUT");
	$result = fclose ($fp);
}

function logoutEmail ($fp) {
	$result = sendCommand($fp, "CLOSE");
	$result = sendCommand($fp, "LOGOUT");
	$result = fclose ($fp);
}

function mimeDecode ($str) {
	$origStr = trim($str);

	mb_internal_encoding('UTF-8');
	
	$str = mb_decode_mimeheader($origStr);
	
	if (strpos($origStr, '=?') === 0) {
		$str = str_replace('_', ' ', $str);
	}
	
	return $str;
}

function fetchPartIntoFile ($fp, $uid, $part, $encoding, $fileName, $log = false){
	
	fj ("filename: $fileName");
	
	$handle = fopen ($fileName, "wb");
	
	if ($handle === false) {
		fj ("fetchPartIntoFile: unable to open $fileName");
		return false;
	}
	
	$id = "C".randStrGen(10);
	
	$command = "UID FETCH ".$uid." (BODY.PEEK[$part])";
	
	if ($log) {
		fj ($command);
	}
	
	$command = "$id $command\r\n";
	
    fwrite($fp, $command);
	
	$flag_later = false;
	$count = 0;
	
	$firstLine = fgets($fp);
	if (stripos ($firstLine, $id) !== false) {return $firstLine;}
	
	while ($line = fgets($fp)) {
		
		if (stripos ($line, $id) !== false) {
			break;
		}

		switch ($encoding) {
			case 'quoted-printable':
				$line = trim($line);
				$line = quoted_printable_decode ($line);		
				break;

			case '7bit':
			case '8bit':
				break;	
		
			case 'base64':
				//mb_internal_encoding('ASCII');
				$line = trim($line);
				$line = base64_decode ($line);
				break;
			
			default:
				fj ("Server Error: Unknown encoding: ".$encoding." for requested file", "fetchPartIntoFile Alert:");
		}
		
		fwrite ($handle, $line);
	}
	
	fclose ($handle);
	
	if ($log) {
		fj ($line);
	}
	
	return true;
}


function parsePart ($part) {
	
	$exploded = explode (" ", $part);
	
	$num = count ($exploded);
	
	$curVal = 0;
	
	$value = array();
	
	$value[$curVal] = "";
	
	for ($i = 0; $i < $num; ++$i) {
		if (strlen ($value[$curVal]) === 0){
			$numQuotes = 0;
			$left = 0;
			$right = 0;
				
			if (strncmp($exploded[$i], '"', 1) === 0)
			{
				$flag_isQuote = true;
			} else {
				$flag_isQuote = false;
			} 
		}
		
		$value[$curVal] .= $exploded[$i]." ";
		
		$left += substr_count($exploded[$i],"(");
		$right += substr_count($exploded[$i],")");
		$numQuotes += substr_count($exploded[$i],'"');
		
		if ($left === $right) { 
			if ($flag_isQuote) {
				if ($numQuotes % 2 !== 0) {
					continue;
				}
			}
			++$curVal;
			$value[$curVal] = "";

		}
	}
	
	$num = count ($value);
	
	for ($i = 0; $i < $num; ++$i) {
		$value[$i] = trim($value[$i]);
	}
	
	// Note: If you are having bugs check $value here to see if the above code needs to be more robust
	
	//printWell ($value); exit ();
	
	return ($value);
	
}

function isNumericPart ($component) {
	$pattern = '~^[0-9]+[\0560-9]*$~';
	
	if (preg_match($pattern, $component, $match)) {
		return true;
	}
	
	return false;
}

function extractParts ($bs) {
	
	$numParts = count($bs);

	$parts = array();
	
	$flagHTML = false;
	$flagPlain = false;
	
	for ($i = 0; $i < $numParts; ++$i) {
		$parts[$i] = array();
		
		$bs[$i] = trim($bs[$i]);
		
		
		$parts[$i]['text'] = $bs[$i];
		
		$value = parsePart ($bs[$i]);
		$numValues = count($value);
		
		// descriptor part
		
		if ($numValues < 7) {
			$parts[$i]['use'] = 'descriptor';
			continue;
		}
		
		// non-numeric part
			// non-numeric parts are always the email body
		
		if (!isNumericPart($value[0])) {
			$parts[$i]['partNum']		= 1;
		
			$parts[$i]['type'] 		= strtolower($value[0]);
			$parts[$i]['type'] 		= str_replace('"', "", $parts[$i]['type']);

			if (strcmp($parts[$i]['type'], 'text') !== 0) {
				$tmp15 = str_replace('"', '', $parts[$i]['type']);
				addDebug('imap.php', 'extractParts', "Unknown use of type [$tmp15] for non-numeric part.", $bs);
			}
		
			$parts[$i]['subtype']	= strtolower($value[1]);
			$parts[$i]['subtype'] 	= str_replace('"', "", $parts[$i]['subtype']);

			$parts[$i]['charset'] 	= $value[2];
		
			$parts[$i]['encoding'] 	= strtolower($value[5]);
			$parts[$i]['encoding'] 	= str_replace('"', "", $parts[$i]['encoding']);
		
			$parts[$i]['size']		= intval ($value[6]);

			if (strcmp($parts[$i]['subtype'], "html") === 0) {
				$parts[$i]['use'] = 'bodyHtml';
			} else {
				$parts[$i]['use'] = 'bodyPlain';
				// important: strip HTML tags if use = bodyText
			}
			continue;
		} 
	
		// numeric part
			// identified numeric parts are either body (bodyHtml or bodyPlain), inline, attachment.  unknown is treated as attachment by client
		
		$parts[$i]['partNum']	= $value[0];
		$parts[$i]['type'] 		= strtolower($value[1]);
		$parts[$i]['subtype']	= strtolower($value[2]);
		$parts[$i]['property'] 	= $value[3];
		$parts[$i]['id'] 		= $value[4];
		$parts[$i]['encoding'] 	= strtolower($value[6]);
		$parts[$i]['size']		= intval ($value[7]);
	
		$parts[$i]['type'] 		= str_replace('"', "", $parts[$i]['type']);
		$parts[$i]['subtype'] 	= str_replace('"', "", $parts[$i]['subtype']);
		$parts[$i]['encoding'] 	= str_replace('"', "", $parts[$i]['encoding']);
	
		if (strcmp($parts[$i]['type'], 'text') === 0) {
			$parts[$i]['desc'] = $value[10];
			$flag_isText = true;
			
		} else {
			$parts[$i]['desc'] = $value[9];
			$flag_isText = false;
		}
	
		$parts[$i]['use'] = 'unknown';
		$parts[$i]['filename'] = "";
		
		// look for bodies first
		
		if ($flag_isText && !$flagHTML) {
			switch ($parts[$i]['subtype']) {
				case 'plain':
					if (!$flagPlain) {
						//echo "GOT FLAG PLAIN<br>";
						$parts[$i]['use'] = 'bodyPlain';
						$flagPlain = true;
					}
					break;
				case 'html':
					$parts[$i]['use'] = 'bodyHtml';
					$flagHTML = true;
					break;
				default:
			}
			
			if (strcmp('unknown', $parts[$i]['use']) !== 0) {
				continue;	
			}
		}
		
		if ($flag_isText) {
			switch ($parts[$i]['subtype']) {
				case 'plain':
					$parts[$i]['use'] = 'textPlain';
					break;
				
				case 'html':
					$parts[$i]['use'] = 'textHtml';
					break;
					
				default:
					$parts[$i]['use'] = 'textPlain';
					break;
				
			}
		}
				
		
		// look for inlines and attachments
		
		// type 1
		
		if (strncmp ($parts[$i]['desc'], "(\"inline\" (\"filename\" )", 22) === 0) {
			$parts[$i]['use'] = 'inline';
			
			$pattern = '~\"filename\"\s\"(.*?)\"~i';
	
			if (preg_match ($pattern, $parts[$i]['desc'], $match)) {
				$parts[$i]['filename'] = $match[1];
				continue;
			}	
		}
		
		if (strncmp ($parts[$i]['desc'], "(\"attachment\" (\"filename\" )", 26) === 0) {
			$parts[$i]['use'] = 'attachment';
			
			$pattern = '~\"filename\"\s\"(.*?)\"~i';
	
			if (preg_match ($pattern, $parts[$i]['desc'], $match)) {
				$parts[$i]['filename'] = $match[1];
				continue;
			}	
			
		}
		
		if (stristr($parts[$i]['desc'], 'INLINE') !== false) {
			$parts[$i]['use'] = 'inline';
			
			$pattern = '~\"name\"\s\"(.*?)\"~i';
	
			if (preg_match ($pattern, $parts[$i]['property'], $match)) {
				$parts[$i]['filename'] = $match[1];
				if (strlen ($parts[$i]['filename']) > 0) {
					continue;
				}
			} 
		}
		
		
		if (stristr($parts[$i]['desc'], 'attachment') !== false) {
			$parts[$i]['use'] = 'attachment';
			
			$pattern = '~\"name\"\s\"(.*?)\"~i';
	
			if (preg_match ($pattern, $parts[$i]['property'], $match)) {
				$parts[$i]['filename'] = $match[1];
				if (strlen ($parts[$i]['filename']) > 0) {
					continue;
				}
			} 
		}
		
		
		// if use is unknown but property has a filename then assign that filenae as filename and continue
		
		$pattern = '~\"filename\"\s\"(.*?)\"~i';
	
		if (preg_match ($pattern, $parts[$i]['property'], $match)) {
			$parts[$i]['filename'] = $match[1];
			if (strlen ($parts[$i]['filename']) > 0) {
				continue;
			}
		} 
		
		// if use is unknown but property has a name then assign that name as filename and continue
		
		$pattern = '~\"name\"\s\"(.*?)\"~i';
	
		if (preg_match ($pattern, $parts[$i]['property'], $match)) {
			$parts[$i]['filename'] = $match[1];
			if (strlen ($parts[$i]['filename']) > 0) {
				continue;
			}
		} 
		
		//sendAlert ($bs[$i]);
		
		//if ((isSame($parts[$i]['desc'],'NIL')) && (isSame($parts[$i]['property'],'NIL')) && (isSame($parts[$i]['id'],'""'))) {
		
		
		
		
		$debugFlag = false;
		
		if ((isSame($parts[$i]['use'], 'unknown'))) {
			$debugFlag = true;
		}
		
		if ((isSame($parts[$i]['use'], 'attachment')) && (strlen($parts[$i]['filename']) === 0)) {
			$debugFlag = true;
		}
		
		if ((isSame($parts[$i]['use'], 'inline')) && (strlen($parts[$i]['filename']) === 0)) {
			$debugFlag = true;
		}
		/*
		if ($debugFlag) {
			echo $bs[$i]."<br><br>";
			printWell ($bs);
			echo "<br><br>";
			printWell ($parts);
			echo "<br><br>";
		}
		*/
		
		if (strlen($parts[$i]['filename']) === 0) {
			$root = 'terra-'.strtolower(randStrGen(10));
			$test = $parts[$i]['type'].'-'.$parts[$i]['subtype'];
			
			switch ($test) {
				case 'text-html':
					$end = '.html';
					$debugFlag = false;
					break;
					
				case 'text-plain':
					$end = '.txt';
					$debugFlag = false;
					break;

				case 'text-calendar':
					$end = '.ics';
					$debugFlag = false;
					break;
					
				case 'image-png':
					$end = '.png';
					$debugFlag = false;
					break;
					
				case 'image-gif':
					$end = '.gif';
					$debugFlag = false;
					break;
					
				case 'image-jpeg':
					$end = '.jpg';
					$debugFlag = false;
					break;
				
				case 'image-jpg':
					$end = '.jpg';
					$debugFlag = false;
					break;				
					
				default:
					$end = '.bin';
					$test = str_replace('"', '', $test);
					
					addDebug('imap.php', 'extractParts', "Unknown file type [$test] for part", $bs);
					
			}
			
			$parts[$i]['filename'] = $root.$end;
		}
		
		$pattern = '~\"~g';
		if ( (isSame($parts[$i]['desc'],'NIL')) && (isSame($parts[$i]['property'],'NIL')) && (strlen(preg_replace($pattern, '', $parts[$i]['id'])) === 0) ) {
			$debugFlag = false;
			
			//truly unknown
		}
		
		if ($debugFlag) {
			//sendAlert ("Use : " . $parts[$i]['use'] . "\nFile: " . $parts[$i]['filename'], "Debug");
		}
		// handle unknowns
		
		// debug only
		//sendAlert ($bs[$i]."\n".json_encode($bs), "extractParts Alert 2:");
	}
	
	$numParts = count($parts);
	
	for ($i = 0; $i < $numParts; ++$i) {
		switch ($parts[$i]['use']) {
			case 'inline':
			case 'attachment':
			case 'unknown':
				if (strlen($parts[$i]['filename']) === 0) {
					sendAlert ($parts[$i]['text']);
				}
				break;
			default:
				break;
		}
	}
	
	return $parts;
}

function parseHeader ($result) {
	
	// Insert Delimiter based on search terms: e.g. \n\wFrom: becomes \n\wDELIMETERFrom: 
	
	/*
	if (preg_match("/Employment Letter/", $result)) {
		sendAlert ($result);
	}
	*/
	
	$lines = explode ("\n", $result);
	
	$email = array ();
	
	//$email['input'] = $result;
				
	$email['sequence'] = "";
	$email['uid'] = 0;
	$email['subject'] = "";
	$email['date'] = "";
	$email['to'] = "";
	$email['from'] = "";
	$email['cc'] = "";
	$email['bcc'] = "";
	$email['messageId'] = "";
	$email['references'] = "";
	
	//$email['flags'] = "";
	$email['isSeen'] = "no";
	$email['isFlagged'] = "no";
	$email['isDeleted'] = "no";
	$email['isAnswered'] = "no";
	$email['bodystructure'] = "";
	$email['error'] = "no";
	
	$count = 0;

	$target = array();
	$target[] = "\r";
	$target[] = "\n";
	$target[] = "\t";
	
	foreach ($lines as $line) {
	
		trim($line);
		
		++$count;
		
		$flagContinue = true;
		
		if($count === 1) {
			
			if (!preg_match ("/[0-9]+/", $line, $match)) {
				echo "Error: Could not locate sequence number in $line in " . print_r($result);	
				return false;
			}
					
			$email['sequence'] = $match[0];
			
			// get uid
			
			if (!preg_match ("/.*UID ([0-9]+)/", $line, $match)) {
				echo "Error: Could not locate UID in $line.";
				return false;
			}
			
			$email['uid'] = $match[1];
			
			$flagContinue = false;
			
			// get flags
		}
			
		if (preg_match ("/.*FLAGS\s(\(.*?\))\s/", $line, $match)) {
				//sendAlert ("Error: Could not locate FLAGS in $line.");
				//$email['flags'] = $match[1];
				$flags = $match[1];
			
				if (strpos ($flags, "\Seen")) {
					$email['isSeen'] = 'yes';
				}
				
				if (strpos ($flags, "\Answered")) {
					$email['isAnswered'] = 'yes';
				}
				
				if (strpos ($flags, "\Flagged")) {
					$email['isFlagged'] = 'yes';
				}
				
				//sendAlert ($result);
			$flagContinue = false;
		}
/*		
		
*/		
		if (preg_match ("/^Date:\s*(.*)/i", $line, $match)) {
			$email['date'] = trim($match[1]);
			continue;
			
		} else if (preg_match ('/^Date:\s(.*)/i', $line, $match)) {
			$email['date'] = $match[1];
			continue;
		}	
			
		if (preg_match ("/^Subject:\s*(.*)/i", $line, $match)) {
			$email['subject'] = mimeDecode ($match[1]);
			
			
			$last = 'subject';
			if (strpos($line, "Holiday Gift Guide Pitching")) {
				//sendAlert (json_encode($result));
			}
			continue;
		}
					
		if (preg_match ("/^From:\s*(.*)/i", $line, $match)) {
			$email['from'] = trim(str_replace($target, "", mimeDecode ($match[1])));
			$email['fromLine'] = $line;
			$email['fromMatch'] = $match[1];
			$last = 'from';
			continue;
		}
		
		if (preg_match ("/^To:\s*(.*)/i", $line, $match)) {
			$email['to'] = trim(str_replace($target, "", mimeDecode ($match[1])));
			$last = 'to';
			continue;			
		}
					
		if (preg_match ("/^Cc:\s*(.*)/i", $line, $match)) {
			$email['cc'] = trim(str_replace($target, "", mimeDecode ($match[1])));
			$last = 'cc';
			continue;
		}
					
		if (preg_match ("/^Message-ID: (.*)/i", $line, $match)) {
			$email['messageId'] = trim($match[1]);
			$last = 'messageId';
			continue;
		}
					
		if (preg_match ("/^References: (.*)/i", $line, $match)) {
			$email['references'] = trim($match[1]);
			$last = 'references';
			continue;
		}
		
		$loc = strpos ($line, 'BODYSTRUCTURE');
		
		if ($loc !== false) {
			$subString = getParenContents(substr($line, $loc + 13));
		
			$email['bodystructure'] = rawParseBodystructure ('1 (BODYSTRUCTURE '.$subString.')');	
			
			continue;

		}
		
		if ($flagContinue) {
			//sendAlert ($last.":".$line);
			if (isset($last)) {
				$line = mimeDecode($line);
				$email[$last] .= trim($line);
			}
		}
	}


	if (strlen($email['from']) === 0) {
		$email['from'] = 'error';
	}
	
	if (strlen($email['uid']) === 0){
		sendAlert ($result,  "parseHeader Alert 3:");
	}
	
	return $email;
}

function tableDesktop ($email) {
	$numRows = count($email);
	
	$table = '<table id="emailTable" style="width:100%">';
	
	for ($i = 0; $i < $numRows; ++$i) {
		$table .= "<tr>";
		$uid = $email[$i]['uid'];
		
		$id = 'hidden-'.$uid;
		$hidden = '<span id='.q($id).' style="display:none;">This is hidden info for '.$id.'</span>';
		
			//checkbox
		
			$id = 'checkbox-'.$uid;
			$value = '<input type="checkbox" name='.d('in'.$id).' id='.d('in'.$id).'>';
		
			$table .= '<td id='.d($id).'>'.$value.'</td>';
			
			if (strpos($email[$i]['flags'], '\Seen')) {
				$seenClass = "isSeen";
				$value = '<i class="fas fa-circle darkColor"></i>';
			} else {
				$seenClass = "notSeen";
				$value = '<i class="far fa-circle darkColor"></i>';
			}
			
			$id = 'circle-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';

		// from
		
			$value = $email[$i]['from'];
			$id = 'from-'.$uid;
			$table .= '<td id='.d($id).' class='.d($seenClass).'>'.$value.'</td>';
		
			//flagged
		
			$value = "";
			if (strpos($email[$i]['flags'], '\Flagged') !== false) {
				$value = '<i class="fas fa-star darkColor"></i>';
			}
			$id = 'flagged-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';
		
			//reply
		
			$value = "";
			if (strpos($email[$i]['flags'], '\Answered') !== false) {
				$value = '<i class="far fa-reply darkColor"></i>';
			}
			$id = 'reply-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';
	
			// subject
		
			$value = $email[$i]['subject'];
			$id = 'subject-'.$uid;
			$table .= '<td id='.d($id).' class='.d($seenClass).'>'.$value.$hidden.'</td>';
			
			// date
		
			$value = $email[$i]['date'];
			$id = 'date-'.$uid;
			$table .= '<td id='.d($id).' class='.d($seenClass).'>'.$value.'</td>';
		
			
			// attachments
			
		$table .="</tr>";
	}
	
	$table .= "</table>";
	
	return $table;
}

function tableTablet ($email) {
	$numRows = count($email);
	
	$table = '<table id="emailTable">';
	
	for ($i = 0; $i < $numRows; ++$i) {
		$table .= "<tr>";
			$uid = $email[$i]['uid'];
		
				
			//checkbox
		
			$id = 'checkbox-'.$uid;
			$value = '<input type="checkbox" name='.d('in'.$id).' id='.d('in'.$id).'>';
		
			$table .= '<td id='.d($id).'>'.$value.'</td>';
		
		
				// set seen class and circle
		
			if (strpos($email[$i]['flags'], '\Seen')) {
				$seenClass = "isSeen";
				$value = '<i class="fas fa-circle darkColor"></i>';
			} else {
				$seenClass = "notSeen";
				$value = '<i class="far fa-circle darkColor"></i>';
			}
			
			$id = 'circle-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';

			//flagged
		
			$value = "";
			if (strpos($email[$i]['flags'], '\Flagged') !== false) {
				$value = '<i class="fas fa-star darkColor"></i>';
			}
			$id = 'flagged-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';
		
			//reply
		
			$value = "";
			if (strpos($email[$i]['flags'], '\Answered') !== false) {
				$value = '<i class="far fa-reply darkColor"></i>';
			}
			$id = 'reply-'.$uid;
			$table .= '<td id='.d($id).'>'.$value.'</td>';
	
			// subject
		
			$value = '<div><div style="float:left">'.$email[$i]['from'].'</div><div style="float:right">'.$email[$i]['date'].'</div><div style="clear: both">'.$email[$i]['subject']."</div></div>";
			$id = 'subject-'.$uid;
			$table .= '<td id='.d($id).' class='.d($seenClass).'>'.$value.'</td>';
		
			// attachments
			
		$table .="</tr>";
	}
	
	$table .= "</table>";
	
	return $table;
}

function tableMobile ($email) {
	$numRows = count($email);
	
	$table = '<table id="emailTable" style="padding-right:5em; width:95%; ">';
	
	for ($i = 0; $i < $numRows; ++$i) {
		$test = $i % 2;

		$table .= '<tr>';
			$uid = $email[$i]['uid'];
		
				// set seen class and circle
		
			if (strpos($email[$i]['flags'], '\Seen')) {
				$seenClass = "isSeen";
				$value = '<i class="fas fa-circle darkColor"></i>';
			} else {
				$seenClass = "notSeen";
				$value = '<i class="far fa-circle darkColor"></i>';
			}

			//flagged
		
			$flagged = "";
			if (strpos($email[$i]['flags'], '\Flagged') !== false) {
				$flagged = '<i class="fas fa-star darkColor"></i>&nbsp;';
			}
			
	
			// from/date/subject
		
			
			$value = '<div><div style="float:left">'.$email[$i]['from'].'</div><div style="float:right">'.$email[$i]['date'].'</div><div style="clear: both">'.$flagged.$email[$i]['subject']."</div></div>";
			$id = 'subject-'.$uid;
			$table .= '<td id='.d($id).' class='.d($seenClass).'>'.$value.'</td>';
		
			// attachments + reply
		
			$reply = "";
			if (strpos($email[$i]['flags'], '\Answered') !== false) {
				$reply = '<i class="far fa-reply darkColor"></i>';
			}
			
			$attachment = '<i class="fas fa-paperclip darkColor"></i>';
		
			$id = 'reply-'.$uid;
			$table .= '<td id='.d($id).' valign="top">'.$attachment.'</td>';
	
		
			
		$table .="</tr>";
	}
	
	$table .= "</table>";
	
	return $table;
}

function createEmailTable ($email, $format) {
	
	return tableDesktop ($email);
	
	switch ($format) {
		case 'desktop':
			return tableDesktop($email);
			break;
			
		case 'tablet':
			break;
			
		case 'mobile':
			break;
			
		default:
			return tableDesktop($email);
	}
}