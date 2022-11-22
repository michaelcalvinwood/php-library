<?php
function showDatabases ($db) {
	$databases = array();
	
	$query = "SHOW DATABASES";
	
	$result = mysqli_query($db, $query);
	
	if ($result === false) {
		exit ("Database error in showDatabases()");
	}
	
	$numRows = mysqli_num_rows ($result);
	
	for ($i = 0; $i < $numRows; ++$i) {
		$row = mysqli_fetch_row($result);
		$databases[] = $row[0];
	}
	
	mysqli_free_result ($result);
	
	return $databases;
	
}