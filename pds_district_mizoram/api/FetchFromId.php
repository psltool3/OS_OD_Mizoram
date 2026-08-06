<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');

if(!SessionCheck()){
	return;
}

$district = $_SESSION['district_district'];
$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$numrow = mysqli_num_rows($result);
$id = "";
$rolled_out = "0";
if($numrow>0){
	$row = mysqli_fetch_assoc($result);
	$id = $row['id'];
	$rolled_out = isset($row['rolled_out']) ? $row['rolled_out'] : "0";
}

if ($rolled_out != '1' || empty($id)) {
	echo json_encode(array());
	exit();
}

$tablename = "optimiseddata_".$id;
$result = $con->query("SELECT DISTINCT from_id from $tablename WHERE to_district='$district'");

if ($result->num_rows > 0) {
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
}
?>