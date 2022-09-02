<?php


function randStrGen ($len) {
	$chars = array ('A', 'B', 'C', 'D', 'E', 'F', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '7', '8', '9');
	$numChars = count($chars);
	
	$result = "";
	
	for ($i = 0; $i < $len; ++$i) {
		$choice = random_int (0, $numChars - 1);
		$result .= $chars[$choice];	
	}
	
	return $result;
}

?>