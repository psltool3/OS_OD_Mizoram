<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('Header.php');
require('../util/Logger.php');

$redirect = '../OptimisedData.php';

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$id = "";
while($row = mysqli_fetch_array($result))
{
	$id= $row["id"];
}
$tablename = "optimiseddata_".$id;

// Check deadline expiry
$query_timer = "SELECT * FROM timer WHERE 1";
$result_timer = mysqli_query($con, $query_timer);
$date = "";
$time = "";
while ($row_timer = mysqli_fetch_array($result_timer)) {
	$date = $row_timer['deadline_date'];
	$time = $row_timer['deadline_time'];
}
if (!empty($date) && !empty($time)) {
	$targetTimestamp = strtotime($date . " " . $time);
	if (time() >= $targetTimestamp) {
		echo "<script>alert('Deadline has expired. Changes cannot be reset.'); window.location.href = '$redirect';</script>";
		exit();
	}
}

foreach ($_POST as $key => $value) {
	if ($value === 'reset') {
		$parts = explode("_", $key, 3);
		$fromid = $parts[0];
		$toid = str_replace('_', '.', $parts[1]);
		$commodity = isset($parts[2]) ? str_replace(['_', '.bool'], ['.', ''], $parts[2]) : '';
		
		$query = "UPDATE " . $tablename . " SET district_change_approve='', approve_admin='', new_id_admin='', new_name_admin='', new_distance_admin='', reason_admin='' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
		mysqli_query($con, $query);
	}
}

echo "<script>window.location.href = '$redirect';</script>";
?>
