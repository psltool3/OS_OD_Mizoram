<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	//return;
}
$message = "";
$month = isset($_POST['month']) ? $_POST['month'] : '';
$year = isset($_POST['year']) ? $_POST['year'] : '';
$months = array('jan', 'feb', 'march', 'april', 'may', 'june', 'july', 'aug', 'sept', 'oct', 'nov', 'dec');
$month_index = array_search(strtolower($month), $months);
$prev_month = '';
$prev_year = '';
if ($month_index !== false) {
	$prev_month_index = $month_index - 1;
	$prev_year = intval($year);
	if ($prev_month_index < 0) {
		$prev_month_index = 11;
		$prev_year = $prev_year - 1;
	}
	$prev_month = $months[$prev_month_index];
}
$query = "SELECT * FROM optimised_table WHERE month='$month' AND year='$year'";
$result = mysqli_query($con,$query);
$response = array();
$response_data = array();
while($row = mysqli_fetch_array($result))
{
	$temp = array();
	$temp["year"] = $row["year"];
	$temp["month"] = $row["month"];
	$temp["id"] = $row["id"];
	$temp["applicable"] = $row["applicable"];
	$temp["last_updated"] = $row["last_updated"];
	array_push($response,$temp);
	$query_approve = "SELECT * FROM optimiseddata_".$row["id"]." WHERE approve_admin<>'yes' OR approve_admin IS NULL";
	$result_approve = mysqli_query($con,$query_approve);
	$numrows_approve = mysqli_num_rows($result_approve);
	if($numrows_approve != 0){
		$message = "Please approve all tags of leg2 first";
	}
}
if(count($response)==0 && !empty($prev_month)){
	$query = "SELECT * FROM optimised_table WHERE month='$prev_month' AND year='$prev_year'";
	$result = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($result))
	{
		$temp = array();
		$temp["year"] = $row["year"];
		$temp["month"] = $row["month"];
		$temp["id"] = $row["id"];
		$temp["applicable"] = $row["applicable"];
		$temp["last_updated"] = $row["last_updated"];
		array_push($response,$temp);
		$query_approve = "SELECT * FROM optimiseddata_".$row["id"]." WHERE approve_admin<>'yes' OR approve_admin IS NULL";
		$result_approve = mysqli_query($con,$query_approve);
		$numrows_approve = mysqli_num_rows($result_approve);
		if($numrows_approve != 0){
			$message = "Please approve all tags of leg2 first";
		}
	}
}
if(count($response)==0){
	$message = "First optimized the leg2 for this month or previous month";
}
$response_data["data"] = $response;
$response_data["message"] = $message;
echo json_encode($response_data);

?>