<?php

/*
 * mcwIterativeBinaryKeySearch
 * purpose: finds the key associated with the provided value
 * The input must be in the form of an indexed array containing key value pairs.
 * E.g.: 
 *      $arr = [];
 *      $arr[0] = ["key1" => "a"];
 *      $arr[1] = ["key2" => "b"];
 * The indexed array must already be sorted in ascending order of the value (use mcwSortIndexedKeyValueArray)
 */

function mcwIterativeBinaryKeySearch($arr, $x) {
	// check for empty array
    if (count($arr) === 0) return false;
	$low = 0;
	$high = count($arr) - 1;
	
	while ($low <= $high) {
		
		// compute middle index
		$mid = floor(($low + $high) / 2);

        $item = $arr[$mid];
		// element found at mid
        $key = key($arr[$mid]);
        $val = $arr[$mid][$key];
    	if($val == $x) {
			return $key;
		}

		if ($x < $val) {
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


$test = [];
$test[0] = ["a" => "a"];
$test[1] = ["b" => "b"];

$result = mcwIterativeBinaryKeySearch($test, "b");

echo "result: $result\n";
?>