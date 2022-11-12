<?php


/*
 $info['indexes'] = array of strings
 $info['unique'] = array of string
 */
function mysqliCreateTable ($db, $tableName, $info) {
	$command = "CREATE TABLE IF NOT EXISTS $tableName (";
	$primaryKey = '';
	$indexes = '';
	$unique = '';

	foreach ($info as $key => $val) {
		if ($key === 'primary') {
			$primaryKey = "PRIMARY ($val)";
			continue;
		}

		if ($key === 'indexes') {
			$numIndexes = count($val);
			for ($i = 0; $i < $numIndexes; ++$i) {
				
			}
		}
	}

}

function mysqliSelectResults ($db_conx, $command) {
	$result = mysqli_query($db_conx, $command);
	
	if ($result === false) {
		return false;
	}
	
	$value = array();
	
	$rows = mysqli_num_rows($result);
	$fields = mysqli_num_fields($result);
	
	if ($rows === 0) {
		return $value;
	}
	
	for ($r = 0; $r < $rows; ++$r) {
		for ($f = 0; $f < $fields; ++$f) {
			$value[$r][$f] = $result[$r][$f];
		}
	}
	
	mysqli_free_result ($result);
	
	return $value;
}

function mysqliRequiredQuery ($db_conx, $command, $errMsg) {
	
	$result = mysqli_query($db_conx, $command);
	
	if ($result !== TRUE) {
		echo $errMsg."<br>";
		echo "Error Desc: ".mysqli_error($db_conx)."<br>";
		exit ();
	}
	
	return $result;
}

function mysqliVerboseQuery ($db_conx, $command) {
	
	$result = mysqli_query ($db_conx, $command);
	
	if ($result === false) {
		echo "<br>mysql error ".mysqli_errno($db_conx).": ".mysqli_error($db_conx)."<br>";
	}
	
	return $result;
}

function displayTable ($db_conx, $tabl) {

	$query = "SELECT * FROM $tabl";

	$result = mysqli_query($db_conx, $query);

	if ($result === false) {
		return;
	}
	
	$numFields = mysqli_num_fields($result);

	if ($numFields > 0) {
	
		echo "<table style='border: 1px solid black; border-collapse: collapse;'>";
		echo "<tr>";
		
		$fields = mysqli_fetch_fields($result);

		$count = count($fields);

		foreach ($fields as $field) {
			echo "<th style='text-align:center; border: 1px solid black;'>".$field->name.'</th>'; 
		}
		
		echo '</tr>';

		$numRows = mysqli_num_rows($result);

		if ($numRows > 0) {
			for ($r = 0; $r < $numRows; ++$r) {
				
				$row = mysqli_fetch_row($result);
	
				echo "<tr>";
				for ($f = 0; $f < $numFields; ++$f) {
					$value = preg_replace('/\</', '&lt;', $row[$f]);
					$value = preg_replace('/\>/', '&gt;', $value);
					$value .= " [".strlen($value)."]";
					echo '<td style="border: 1px solid black;">'.$value.'</td>';
				}
				echo '</tr>';
			}
		}
		
		echo "</table>";
		mysqli_free_result($result);
	}
}	

function mysqliInsertIntoTable ($db, $table, $entries) {
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
