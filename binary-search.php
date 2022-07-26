<?php


function mcwKeyValVal ($keyVal) {
    $key = key($keyVal);
    return $keyVal[$key];
}

function mcwKeyValValCompare($kv1, $kv2) {
	$key1 = key($kv1);
	$key2 = key($kv2);

	$val1 = $kv1[$key1];
	$val2 = $kv2[$key2];

	if ($val1 < $val2) return -1;
	if ($val1 > $val2) return 1;
	return 0;
}

/*
 * mcwIterativeBinaryKeySearch
 * purpose: finds the key associated with the provided value
 * The input must be in the form of an indexed array containing key value pairs.
 * E.g.: 
 *      $arr = [];
 *      $arr[] = ["key1" => "a"];
 *      $arr[] = ["key2" => "b"];
 * The indexed array must already be sorted in ascending order of the value (use mcwSortIndexedKeyValueArray)
 */

function mcwIterativeBinaryKeySearch($arr, $x) {
	// check for empty array
    if (count($arr) === 0) return false;
	$low = 0;
	$high = count($arr) - 1;
	
	while ($low <= $high) {
		
		$mid = floor(($low + $high) / 2);

        $key = key($arr[$mid]);
        $val = $arr[$mid][$key];
    	
        if($val == $x) return $key;

		if ($x < $val) $high = $mid - 1;
		else $low = $mid + 1;
	}
	
	return false;
}

function mcwIterativeBinarySearch(Array $arr, $x) {
	// check for empty array
	if (count($arr) === 0) return false;
	$low = 0;
	$high = count($arr) - 1;
	
	while ($low <= $high) {
		
		// compute middle index
		$mid = floor(($low + $high) / 2);

		// element found at mid
		if($arr[$mid] == $x) {
			return true;
		}

		if ($x < $arr[$mid]) {
			// search the left side of the array
			$high = $mid -1;
		}
		else {
			// search the right side of the array
			$low = $mid + 1;
		}
	}
	
	// If we reach here element x doesnt exist
	return false;
}


// $test = [];
// $test[] = ["key_a" => "a"];
// $test[] = ["key_z" => "z"];
// $test[] = ["key_q" => "q"];
// $test[] = ["key_r" => "r"];
// $test[] = ["key_b" => "b"];

// var_dump($test);
// usort($test, 'mcwKeyValValCompare');
// var_dump($test);

// $result = mcwIterativeBinaryKeySearch($test, "b");

// echo "result: $result\n";
?>