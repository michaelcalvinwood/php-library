<?php
function getXt ($str) {
	$parts = explode('.', $str);
	$count = count ($parts);
	
	return ($parts[$count-1]);
}

function rangeDownloadR($file) {
 
	$fp = @fopen($file, 'rb');
 
	$size   = filesize($file); // File size
	$length = $size;           // Content length
	$start  = 0;               // Start byte
	$end    = $size - 1;       // End byte
	// Now that we've gotten so far without errors we send the accept range header
	/* At the moment we only support single ranges.
	 * Multiple ranges requires some more work to ensure it works correctly
	 * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	 *
	 * Multirange support annouces itself with:
	 * header('Accept-Ranges: bytes');
	 *
	 * Multirange content must be sent with multipart/byteranges mediatype,
	 * (mediatype = mimetype)
	 * as well as a boundry header to indicate the various chunks of data.
	 */
	header("Accept-Ranges: 0-$length");
	//header('Accept-Ranges: bytes');
	// multipart/byteranges
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	if (isset($_SERVER['HTTP_RANGE'])) {
		$c_start = $start;
		$c_end   = $end;
		// Extract the range string
		list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
		// Make sure the client hasn't sent us a multibyte range
		if (strpos($range, ',') !== false) {
		
			// (?) Shoud this be issued here, or should the first
			// range be used? Or should the header be ignored and
			// we output the whole content?
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		// If the range starts with an '-' we start from the beginning
		// If not, we forward the file pointer
		// And make sure to get the end byte if spesified
		if ($range == '-') {
		
			// The n-number of the last bytes is requested
			$c_start = $size - substr($range, 1);
		}
		else {
		
			$range  = explode('-', $range);
			$c_start = $range[0];
			$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
		}
		/* Check the range and make sure it's treated according to the specs.
		 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		 */
		// End bytes can not be larger than $end.
		$c_end = ($c_end > $end) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
		
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		$start  = $c_start;
		$end    = $c_end;
		$length = $end - $start + 1; // Calculate new content length
		fseek($fp, $start);
		header('HTTP/1.1 206 Partial Content');
	}
	// Notify the client the byte range we'll be outputting
	header("Content-Range: bytes $start-$end/$size");
	header("Content-Length: $length");
 
	// Start buffered download
	$buffer = 1024 * 8;
	
	while(!feof($fp) && ($p = ftell($fp)) <= $end) {
	
		if ($p + $buffer > $end) {
		
			// In case we're only outputtin a chunk, make sure we don't
			// read past the length
			$buffer = $end - $p + 1;
		}
		//set_time_limit(0); // Reset time limit for big files
		$stuff = fread($fp, $buffer);
		if ($stuff !== false) echo $stuff;
		flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
	}
 
	fclose($fp);              
}

function rangeDownload($file) {
 
	$fp = @fopen($file, 'rb');
	
	if ($fp === false) {
		exit ();
	}
 
	$size   = filesize($file); // File size
	$length = $size;           // Content length
	$start  = 0;               // Start byte
	$end    = $size - 1;       // End byte
	
	header("Accept-Ranges: 0-$length");
	//header("Accept-Ranges: bytes");
	// header('Accept-Ranges: bytes');
	// multipart/byteranges
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	if (isset($_SERVER['HTTP_RANGE'])) {
	
		$c_start = $start;
		$c_end   = $end;
		// Extract the range string
		list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
		// Make sure the client hasn't sent us a multibyte range
		if (strpos($range, ',') !== false) {
		
			// (?) Shoud this be issued here, or should the first
			// range be used? Or should the header be ignored and
			// we output the whole content?
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		
		// If the range starts with an '-' we start from the beginning
		// If not, we forward the file pointer
		// And make sure to get the end byte if spesified
		if ($range{0} == '-') {
		
			// The n-number of the last bytes is requested
			$c_start = $size - substr($range, 1);
		}
		else {
		
			$range  = explode('-', $range);
			$c_start = $range[0];
			$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
		}
		/* Check the range and make sure it's treated according to the specs.
		 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		 */
		// End bytes can not be larger than $end.
		$c_end = ($c_end > $end) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
		
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		
		$start  = $c_start;
		$end    = $c_end;
		$length = $end - $start + 1; // Calculate new content length
		fseek($fp, $start);
		header('HTTP/1.1 206 Partial Content');
	}
	// Notify the client the byte range we'll be outputting
	header("Content-Range: bytes $start-$end/$size");
	header("Content-Length: $length");
	//header ('Content-Type: video/mp4');
	http_response_code(200);

 
	// Start buffered download
	$buffer = 1024 * 8;
	while(!feof($fp) && ($p = ftell($fp)) <= $end) {
	
		if ($p + $buffer > $end) {
		
			// In case we're only outputtin a chunk, make sure we don't
			// read past the length
			$buffer = $end - $p + 1;
		}
		
		$stuff = fread($fp, $buffer);
		
		if ($stuff !== false) {
			echo $stuff;
		} else {
			break;
		}
		flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		
	}
 
	fclose($fp);
    
}


function getToken ($src) {
	$p1 = explode('/', $src);
	$c = count ($p1);
	$fn = $p1[$c-1];
	
	$p2 = explode('.', $fn);
	
	return ($p2[0]);
	
}

// IMPORTANT: In the future you can add the user id number to the directory so that we can assign $GLOBALS['user] for ll
$GLOBALS['user'] = 'ozzy13@fastmail.com';
//ll ('here we are');
$source = $_SERVER['REQUEST_URI'];
$fn = "";
$type = 'inline';

if (strpos($source, '/email/') === 0) {
	$token = getToken ($source);
	
	$q = 'SELECT fn FROM emailBody WHERE token = ' . q($token);
	
	$db = connectDatabase();
	$r = mysqli_query($db, $q);
	
	if ($r === false) { 
		mysqli_close ($db);
		exit ("Database Error"); 
	}
	
	$nr = mysqli_num_rows($r);
	
	if ($nr === 0) {	
		mysqli_close ($db);
		exit ("Email previously deleted"); 
	}
	
	$row = mysqli_fetch_row($r);
	
	mysqli_close ($db);
	
	$fn = $row[0];
	$size = filesize($fn);
	
	header ('Content-type: text/html; charset=UTF-8');
	header('Content-Length: ' . $size);
	$file1 = @fopen($fn,"rb");
	while(!feof($file1))
	{
		print(@fread($file1, 1024*8));
		ob_flush();
		flush();
	}
	exit ();
}