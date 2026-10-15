<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	return;
}

$month = "";
$year = "";
if (isset($_POST['month']) && !empty($_POST['month'])) {
	$parts = explode('_', $_POST['month']);
	$month = $parts[0];
	if (isset($parts[1])) {
		$year = $parts[1];
	}
}

$id = "";
$rolled_out = "0";
if (!empty($month) && !empty($year)) {
	$query = "SELECT * FROM optimised_table WHERE month='$month' AND year='$year'";
	$result = mysqli_query($con, $query);
	if ($result && mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$id = $row['id'];
		$rolled_out = isset($row['rolled_out']) ? (string)$row['rolled_out'] : "0";
	}
}

if (empty($id)) {
	$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
	$result = mysqli_query($con, $query);
	if ($result && mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$id = $row['id'];
		$rolled_out = isset($row['rolled_out']) ? (string)$row['rolled_out'] : "0";
	}
}

if ($rolled_out !== '1' || empty($id)) {
	$resultarray = [
		"data" => [],
		"implemented" => 0,
		"notimplemented" => 0
	];
	echo json_encode($resultarray);
	return;
}

$tablename = "optimiseddata_".$id;
$district = $_SESSION['district_district'];

$status = isset($_POST['status']) ? $_POST['status'] : "";
$data = array();
$query = "SHOW TABLES LIKE '$tablename'";
$result = $con->query($query);


if ($result && $result->num_rows > 0) {
	$query_implemented = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND status='implemented' AND approve_admin IN ('yes', 'no') AND approve_district IN ('yes', 'no')";
	$result_implemented = mysqli_query($con,$query_implemented);
	$count_implemented = mysqli_num_rows($result_implemented);
	
	$query_notimplemented = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND (status IS NULL OR status = '') AND approve_admin IN ('yes', 'no') AND approve_district IN ('yes', 'no')";
	$result_notimplemented = mysqli_query($con,$query_notimplemented);
	$count_notimplemented = mysqli_num_rows($result_notimplemented);
	
	$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND approve_admin IN ('yes', 'no') AND approve_district IN ('yes', 'no')";
	if($status=="implemented"){
		$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND status='implemented' AND approve_admin IN ('yes', 'no') AND approve_district IN ('yes', 'no')";
	}
	else if($status=="not implemented"){
		$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND (status IS NULL OR status = '') AND approve_admin IN ('yes', 'no') AND approve_district IN ('yes', 'no')";
	}
	
	$result = mysqli_query($con,$query);
	while($row = mysqli_fetch_assoc($result))
	{
		if($row['new_id_admin']!=null or $row['new_id_admin']!=""){
			$wid = $row['new_id_admin'];
			$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
			$result_warehouse = mysqli_query($con,$query_warehouse);
			$numrows_warehouse = mysqli_num_rows($result_warehouse);
			if($numrows_warehouse!=0){
				$row_warehouse = mysqli_fetch_assoc($result_warehouse);
				$row["from_lat"] = $row_warehouse['latitude'];
				$row["from_long"] = $row_warehouse['longitude'];
				$row["from_district"] = $row_warehouse['district'];
			}
			$row["from_id"] = $row['new_id_admin'];
			$row["from_name"] = $row['new_name_admin'];
			$row["distance"] = $row['new_distance_admin'];
		}
		else if(($row['new_id_district']!=null or $row['new_id_district']!="") and $row['approve_admin']=="yes"){
			$wid = $row['new_id_district'];
			$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
			$result_warehouse = mysqli_query($con,$query_warehouse);
			$numrows_warehouse = mysqli_num_rows($result_warehouse);
			if($numrows_warehouse!=0){
				$row_warehouse = mysqli_fetch_assoc($result_warehouse);
				$row["from_lat"] = $row_warehouse['latitude'];
				$row["from_long"] = $row_warehouse['longitude'];
				$row["from_district"] = $row_warehouse['district'];
			}
			$row["from_id"] = $row['new_id_district'];
			$row["from_name"] = $row['new_name_district'];
			$row["distance"] = $row['new_distance_district'];
		}
		$data[] = $row;			
	}
	$resultarray["data"] = $data;
	$resultarray["implemented"] = $count_implemented;
	$resultarray["notimplemented"] = $count_notimplemented;
	echo json_encode($resultarray);
} else {
	$resultarray = [];
	$resultarray["data"] = array();
	echo json_encode($resultarray);
}
?>