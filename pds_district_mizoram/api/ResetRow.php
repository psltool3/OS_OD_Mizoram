<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('Header.php');
require('../util/Logger.php');

$redirect = '../Home.php';

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$id = "";
while($row = mysqli_fetch_array($result))
{
	$id= $row["id"];
}
$tablename = "optimiseddata_".$id;

foreach ($_POST as $key => $value) {
	if ($value === 'reset') {
		$parts = explode("_", $key, 3);
		$fromid = $parts[0];
		$toid = str_replace('_', '.', $parts[1]);
		$commodity = isset($parts[2]) ? str_replace(['_', '.bool'], ['.', ''], $parts[2]) : '';
		
		$query = "UPDATE " . $tablename . " SET approve_district='', new_id_district='', new_name_district='', new_distance_district='', reason_district='' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
		mysqli_query($con, $query);
	}
}

echo "<script>window.location.href = '$redirect';</script>";
?>
