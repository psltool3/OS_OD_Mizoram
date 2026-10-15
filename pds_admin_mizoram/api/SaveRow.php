<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('Header.php');
require('../util/Logger.php');
ini_set('max_input_vars', 23000000000000);
ini_set('memory_limit', '4G');
set_time_limit(300); // Set to 300 seconds (5 minutes), or 0 for no limit

//echo json_encode($_POST);

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$response = array();
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
		echo "<script>alert('Deadline has expired. Changes cannot be saved.'); window.location.href = '../OptimisedData.php';</script>";
		exit();
	}
}
foreach ($_POST as $key => $value) {
	echo $value;
	echo "</br>";
	if (substr($key, -8) === '_approve'){
		$parts = explode("_", $key,3);
		$fromid = $parts[0];
		$toid = $parts[1];
		$commodity = $parts[2];
		$toid = str_replace('_', '.', $toid);
		$commodity = str_replace('_', '.', $commodity);
		$commodity = str_replace('_approve', '', $commodity);
		if($value=="yes"){
			$query = "UPDATE " . $tablename . " SET district_change_approve='yes' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
			writeLog("User ->" ." Save Data | approve district change yes ->". $_SESSION['user'] . "| " . $fromid . " - " . $toid . " - ". $commodity);
		}
		else if($value=="no"){
			$query = "UPDATE " . $tablename . " SET district_change_approve='no' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
			writeLog("User ->" ." Save Data | approve district change no ->". $_SESSION['user'] . "| " . $fromid . " - " . $toid . " - ". $commodity);
		}
		mysqli_query($con,$query);
		echo $query;
	}	
	if (substr($key, -11) === '_iddistance' or substr($key, -9) === '_idreason' or substr($key, -8) === '_approve' or $value===""){
		continue;
	}
	$parts = explode("_", $key,3);
	$fromid = $parts[0];
	$toid = $parts[1];
	$commodity = $parts[2];
	$toid = str_replace('_', '.', $toid);
	$commodity = str_replace('_', '.', $commodity);
	$commodity = str_replace('.bool', '', $commodity);
	if($value=="yes"){
		$query = "UPDATE " . $tablename . " SET approve_admin='yes' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
		writeLog("User ->" ." Save Data | approve admin change yes ->". $_SESSION['user'] . "| " . $fromid . " - " . $toid . " - ". $commodity);
	}
	else if($value=="same"){
		$query = "UPDATE " . $tablename . " SET approve_admin='no' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
		writeLog("User ->" ." Save Data | approve admin change no ->". $_SESSION['user'] . "| " . $fromid . " - " . $toid . " - ". $commodity);
	}
	else if($value=="no"){
		// this case will not fall as we have check for this in js
		$query = "UPDATE " . $tablename . " SET approve_admin='', new_id_admin='' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
	}
	else if($value==""){
		$query = "";
		//$query = "UPDATE " . $tablename . " SET approve_admin='', new_id_admin='' WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
	}
	else{
		$query_name = "SELECT name FROM warehouse WHERE id='$value'";
		$result_name = mysqli_query($con,$query_name);
		$row_name = mysqli_fetch_assoc($result_name);
		$name = $row_name['name'];
		$reason = $_POST[$key."_idreason"];
		$distance = $_POST[$key."_iddistance"];
		$query = "UPDATE " . $tablename . " SET new_id_admin='$value', new_name_admin='$name', approve_admin='no', new_distance_admin='$distance', reason_admin='$reason' WHERE from_id='$fromid' AND to_id='$toid'";
		writeLog("User ->" ." Save Data | approve district change id ->". $_SESSION['user'] . "| " . $fromid . " - " . $toid . "| " . $value);
	}
	mysqli_query($con,$query);
}
mysqli_close($con);

echo "<script>window.location.href = '../OptimisedData.php';</script>";

?>
<?php require('Fullui.php');  ?>