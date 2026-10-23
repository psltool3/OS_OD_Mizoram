<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Validation.php');

if(!SessionCheck()){
	return;
}

require('Header.php');


$id = $_POST["uid"];

$query = "SELECT * FROM dcp WHERE uniqueid='$id'";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);

if($numrows>0){
	$row = mysqli_fetch_assoc($result);
	$status = $row['active'];
	if($status==0){
		if (isDCPValid($row['latitude'], $row['longitude'], $row['demand'])) {
			$query = "UPDATE dcp SET active='1' WHERE uniqueid='$id'";
			mysqli_query($con,$query);
		} else {
			echo "<script>alert('Cannot activate FCI: Latitude must be 0-40, Longitude 65-180, Offered Quantity FRice >= 0'); window.location.href = '../DCP.php';</script>";
			exit();
		}
	}
	else{
		$query = "UPDATE dcp SET active='0' WHERE uniqueid='$id'";
		mysqli_query($con,$query);
	}
}


mysqli_close($con);

echo "<script>window.location.href = '../DCP.php';</script>";

?>
<?php require('Fullui.php');  ?>