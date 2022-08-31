<?php
    $DBUG = false;
    $SUBTYPES = array('MIXED', 'MESSAGE', 'DIGEST', 'ALTERNATIVE', 'RELATED',
        'REPORT','SIGNED','ENCRYPTED','FORM DATA');
    $BODYSTRUCTURE_RE = '/.*\(BODY\w{0,9} (.*)\)/';
    $CONTENT_TYPE_RE = '/\A\s*"(TEXT|APPLICATION|IMAGE|VIDEO|AUDIO)"/i';
    $MULTIPART_SUBTYPE_RE = sprintf('/\A\s*"(%s)"/i', join('|', $SUBTYPES));

	function formatReceivedBodystructure ($str)
	{
		$formatted = $str;
		
		$loc = stripos ($str, "BODYSTRUCTURE");
		
		$formatted = substr ($str, $loc);
		
		$formatted = "11 (".$formatted;
		
		//echo $formatted."<br />";
		
		return $formatted;
	}

    function parseBodystructure($str) {
        global $BODYSTRUCTURE_RE, $DBUG, $CONTENT_TYPE_RE;	
		$str = formatReceivedBodystructure($str);
		//$str = '11 ('.$str.')';
		//echo "str: $str<br>";
		
		preg_match($BODYSTRUCTURE_RE, $str, $matchs);
        if ($matchs == null) { 
            echo 'WARNING: BODYSTRUCTURE text does not match expected pattern.\n';
			echo $str;
			exit ();
        }
        $body = $matchs[1];
        $parts = [];
        if ($DBUG) {
            print("\nBODY:\n" . $body);
        }
        $data = parse_parts($str);
        foreach($data as $val) {
            $multipart_subtype = $val["multipart_subtype"];
            $depth = $val["depth"];
            $text = $val["text"];

            if ($DBUG) {
                print_r(sprintf("(%s, %s, %s)\n", $multipart_subtype, $depth, $text));
            }
            if ($multipart_subtype) {
                $i = sizeof($parts) - 1;
                while($i >= 0 && $depth < $parts[$i]) {
                    $i = $i - 1;
                }
                array_splice($parts, $i + 1 , 0, array(array($depth-1, $multipart_subtype)));
            }
            if (preg_match($CONTENT_TYPE_RE, $text) == true) {
                array_push($parts, array($depth, $text));
            }
        }
        return add_part_nums($parts);
    }
    
	function rawParseBodystructure($str) {
        global $BODYSTRUCTURE_RE, $DBUG, $CONTENT_TYPE_RE;	
		//$str = formatReceivedBodystructure($str);
		//$str = '11 ('.$str.')';
		//echo "str: $str<br>";
		
		preg_match($BODYSTRUCTURE_RE, $str, $matchs);
        if ($matchs == null) { 
            sendAlert ("WARNING: BODYSTRUCTURE text does not match expected pattern.\n$str");
	    }
        $body = $matchs[1];
        $parts = [];
        if ($DBUG) {
            print("\nBODY:\n" . $body);
        }
        $data = parse_parts($str);
        foreach($data as $val) {
            $multipart_subtype = $val["multipart_subtype"];
            $depth = $val["depth"];
            $text = $val["text"];

            if ($DBUG) {
                print_r(sprintf("(%s, %s, %s)\n", $multipart_subtype, $depth, $text));
            }
            if ($multipart_subtype) {
                $i = sizeof($parts) - 1;
                while($i >= 0 && $depth < $parts[$i]) {
                    $i = $i - 1;
                }
                array_splice($parts, $i + 1 , 0, array(array($depth-1, $multipart_subtype)));
            }
            if (preg_match($CONTENT_TYPE_RE, $text) == true) {
                array_push($parts, array($depth, $text));
            }
        }
        return add_part_nums($parts);
    }
    

    function parse_parts($str) {
        global $MULTIPART_SUBTYPE_RE;
        $open_paren_pos = array();
        $result = array();
        for ($i=0; $i<strlen($str); $i++) {
            $ch_pos = $i;
            $char = $str[$i];
            if ($char == "(") {
                array_push($open_paren_pos, $i);
            } else if ($char == ")") {
                $start_pos = array_pop($open_paren_pos);
                $text = substr($str, $start_pos + 1, $ch_pos - $start_pos - 1);
                $depth = sizeof($open_paren_pos);
                preg_match($MULTIPART_SUBTYPE_RE, substr($str, $ch_pos + 1), $matches);
                $multipart_subtype = ($matches)?$matches[1]:'';

                $tmp = array(
                    "multipart_subtype"=>$multipart_subtype,
                    "depth"=>$depth,
                    "text"=>$text
                );
                array_push($result, $tmp);
            }
        }
        return $result;
    }

    function add_part_nums($parts) {
        global $DBUG, $SUBTYPES;
        if ($DBUG) {
            print_r($parts);
        }
        $result = array();
        $partnums = array_fill(0, max($parts)[0], 0);
        foreach($parts as $part) {
            $depth = $part[0];
            $text = $part[1];
            $partnum = '';
            $is_multipart = in_array(strtoupper($text), $SUBTYPES);
            if ($depth > 1) {
                $partnums[$depth - 2] += 1;
                $partnum = join(".", array_slice($partnums, 0, $depth - 1));
            }
            if ($is_multipart) {
                $text = 'MULTIPART/' . strtoupper($text);
            }
            array_push($result, get_part_str($depth, $partnum, $text));
        }

        return $result;
    }
    
    function get_part_str($depth, $partnum, $text) {
        $str = '';
        for($i=1; $i<$depth; $i++)
            $str = $str . "\t";
        return sprintf("%s%s%s%s", $str, $partnum, ($partnum!='')?" ":"", $text);
    }
    
/*
    $body = '3 (BODY (((("TEXT" "PLAIN"  ("charset" "US-ASCII") NIL NIL "QUOTED-PRINTABLE" 2210 76)("TEXT" "HTML"  ("charset" "US-ASCII") NIL NIL "QUOTED-PRINTABLE"3732 99) "ALTERNATIVE")("IMAGE" "GIF"  ("name" "pic00041.gif") "<2__=07BBFD03DDC66BF58f9e8a93@domain.org>" NIL "BASE64" 1722)("IMAGE" "GIF"  ("name" "ecblank.gif") "<3__=07BBFD43DFC66BF58f9e8a93@domain.org>" NIL "BASE64" 64) "RELATED")("APPLICATION" "PDF"  ("name" "Quote_VLQ5069.pdf") "<1__=07BBED03DFC66BF58f9e8a93@domain.org>" NIL "BASE64" 59802) "MIXED"))';
    
	$test1 = '20310 FETCH (BODYSTRUCTURE ((("TEXT" "PLAIN" ("charset" "UTF-8") NIL NIL "7bit" 4 1)("TEXT" "HTML" ("charset" "UTF-8") NIL NIL "7bit" 4 1) "ALTERNATIVE" ("boundary" "----=_Part_18_28612235.1384442157276"))("IMAGE" "PNG" ("name" "Screen Shot 2013-11-14 at 4.15.41 PM.png") NIL NIL "base64" 13858 NIL ("attachment" ("filename" "Screen Shot 2013-11-14 at 4.15.41 PM.png" "size" "10127"))) "MIXED" ("boundary" "----=_Part_17_22578400.1384442157276")) UID 22049)';

	$test2 = '111 (BODYSTRUCTURE ((("TEXT" "PLAIN" NIL NIL NIL "7BIT" 170 7 NIL NIL NIL)("TEXT" "HTML" ("CHARSET" "utf-8") NIL NIL "7BIT" 1321 24 NIL NIL NIL) "ALTERNATIVE" ("BOUNDARY" "=-GgGWuVS+goa+7OHIJWr0") NIL NIL)("TEXT" "X-PATCH" ("NAME" "fix_class_signals.diff" "CHARSET" "UTF-8") NIL NIL "7BIT" 9541 266 NIL ("ATTACHMENT" ("FILENAME" "fix_class_signals.diff")) NIL) "MIXED" ("BOUNDARY" "=-RBJ0QoWwq+KaBoV5H8JN") NIL NIL))';

	$test3 = '112 (BODYSTRUCTURE ((("TEXT" "PLAIN" ("charset" "UTF-8") NIL NIL "7bit" 4 1)("TEXT" "HTML" ("charset" "UTF-8") NIL NIL "7bit" 4 1) "ALTERNATIVE" ("boundary" "----=_Part_18_28612235.1384442157276"))("IMAGE" "PNG" ("name" "Screen Shot 2013-11-14 at 4.15.41 PM.png") NIL NIL "base64" 13858 NIL ("attachment" ("filename" "Screen Shot 2013-11-14 at 4.15.41 PM.png" "size" "10127"))) "MIXED" ("boundary" "----=_Part_17_22578400.1384442157276")) UID 22049)';

	$test4 = '21 (BODYSTRUCTURE (("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 4 2 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0464.JPG" "X-APPLE-PART-URL" "64991CBD-026B-4296-A58D-FCF5FCCCB797") NIL NIL "BASE64" 117198 NIL ("INLINE" ("FILENAME" "IMG_0464.JPG")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 6 3 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0465.JPG" "X-APPLE-PART-URL" "721BA484-C6C8-44B7-9FBE-12B063CFD1EB") NIL NIL "BASE64" 104910 NIL ("INLINE" ("FILENAME" "IMG_0465.JPG")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 6 3 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0466.JPG" "X-APPLE-PART-URL" "EDD9CD9B-DDE3-4CC8-AF65-3562A482C72B") NIL NIL "BASE64" 100338 NIL ("INLINE" ("FILENAME" "IMG_0466.JPG")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 6 3 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0463.JPG" "X-APPLE-PART-URL" "17C56F66-854B-4FA3-9F97-C96BB2D7FE4E") NIL NIL "BASE64" 111734 NIL ("INLINE" ("FILENAME" "IMG_0463.JPG")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 6 3 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0462.jpg" "X-APPLE-PART-URL" "3D5355EF-13EF-4902-B366-060FF2DE2F31") NIL NIL "BASE64" 126582 NIL ("INLINE" ("FILENAME" "IMG_0462.jpg")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 6 3 NIL NIL NIL)("IMAGE" "JPEG" ("NAME" "IMG_0467.JPG" "X-APPLE-PART-URL" "D0E77B8F-8B0E-42FE-9A64-AE4DA7F606F7") NIL NIL "BASE64" 114264 NIL ("INLINE" ("FILENAME" "IMG_0467.JPG")) NIL)("TEXT" "PLAIN" ("CHARSET" "us-ascii") NIL NIL "7BIT" 23 2 NIL NIL NIL) "MIXED" ("BOUNDARY" "Apple-Mail-01B01D1F-AE33-42CB-98F6-459AAC84733C") NIL NIL))';

	$parts = parse_bodystructure($test4);
    
    if ($parts) {
        for($i=0; $i<sizeof($parts); $i++) {
            print_r($parts[$i]);
            print("<br />");
        }
    }
    
*/