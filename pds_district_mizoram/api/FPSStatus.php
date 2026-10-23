<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php'); 
require('../util/Validation.php');

if(!SessionCheck()){
	return;
}

require('Header.php');

$id = $_POST["uid"];

$query = "SELECT * FROM fps WHERE uniqueid='$id'";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);

if($numrows>0){
	$row = mysqli_fetch_assoc($result);
	$status = $row['active'];
	$fpsname = $row['name'];
	if($status==0){
		if (isFPSValid($row['latitude'], $row['longitude'], $row['demand'])) {
			$query = "UPDATE fps SET active='1' WHERE uniqueid='$id'";
			writeLog("District User ->" ." FPS Active -> ". $_SESSION['district_user'] . "| " . $fpsname);
			mysqli_query($con,$query);
		} else {
			writeLog("District User ->" ." FPS Activation Blocked (Invalid Data) -> ". $_SESSION['district_user'] . "| " . $fpsname);
			echo "<script>alert('Cannot activate FPS: Latitude must be 0-40, Longitude 65-180, Demand of FRice >= 0'); window.location.href = '../FPS.php';</script>";
			exit();
		}
	}
	else{
		$query = "UPDATE fps SET active='0' WHERE uniqueid='$id'";
		writeLog("District User ->" ." FPS InActive -> ". $_SESSION['district_user'] . "| " . $fpsname);
		mysqli_query($con,$query);
	}
}


mysqli_close($con);

echo "<script>window.location.href = '../FPS.php';</script>";

?>
<?php require('Fullui.php');  ?>