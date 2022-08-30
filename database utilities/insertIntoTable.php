<?php

function mcwInsertIntoTable ($db, $table, $entries) {
	$command = "INSERT INTO $table ";
	$columns = '(';
	$values = '(';
	
	$numEntries = count($entries);
	$count = 1;
	
	foreach ($entries as $key => $value ) {
		if ($count !== $numEntries) {
			$columns .= "$key, ";
			if (stripos($value, 'NOW()') === false) {
				$values	 .= "'".$value."', ";
			} else {
				$values	 .= $value.", ";
			}
		} else {
			$columns .= "$key)";
			if (stripos($value, 'NOW()') === false) {
				$values	 .= "'".$value."')";
			} else {
				$values	 .= $value.")";
			}
		}
		++$count;
	}
	
	$command .= $columns." VALUES ".$values;
	
	$result = mysqli_query ($db, $command);
	
	//echo $command;
	
	if ($result === false) {
		echo $command."<br>";
		echo mysqli_error($db);
	}
}

function mcwWpInsertIntoTable ($table, $entries) {
    global $wpdb;
    
	$command = "INSERT INTO $table ";
	$columns = '(';
	$values = '(';
	
	$numEntries = count($entries);
	$count = 1;
	
	foreach ($entries as $key => $value ) {
		if ($count !== $numEntries) {
			$columns .= "$key, ";
			if (stripos($value, 'NOW()') === false) {
				$values	 .= "'".$value."', ";
			} else {
				$values	 .= $value.", ";
			}
		} else {
			$columns .= "$key)";
			if (stripos($value, 'NOW()') === false) {
				$values	 .= "'".$value."')";
			} else {
				$values	 .= $value.")";
			}
		}
		++$count;
	}
	
	$command .= $columns." VALUES ".$values;
	
    dbDelta($command);

}
