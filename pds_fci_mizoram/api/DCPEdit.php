<?php

require('../util/Connection.php');
require('../structures/DCP.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Security.php');
require('../util/Encryption.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');


function formatName($name) {
	$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
    $name = strtoupper($name);
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    // Check if the value is a number and not a string
    if (!is_numeric($value)) {
        return false;
    }
	
    // Convert the value to a float
    $coordinate = floatval($value);

    // Check if it's latitude or longitude and validate within the range
    switch ($coordinateType) {
        case 'latitude':
            return ($coordinate >= -90 && $coordinate <= 90);
        case 'longitude':
            return ($coordinate >= -180 && $coordinate <= 180);
        default:
            return false;
    }
}

function isStringNumber($stringValue) {
    return is_numeric($stringValue) && preg_match('/^\d+(\.\d{1,2})?$/', trim($stringValue)) && floatval($stringValue) >= 0;
}

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(empty($row) || !password_verify($person->getPassword(), $row['password'])){
	echo "Error : Password or Username is incorrect";
	return;
}

if(!isValidCoordinate($_POST["latitude"],'latitude') or !isValidCoordinate($_POST["longitude"],'longitude')){
	echo "Error : Check Latitude and Longitude Value";
	exit();
}

if (!is_numeric($_POST["latitude"]) || floatval($_POST["latitude"]) >= 40) {
	echo "Error : Latitude must be less than 40. Given: " . $_POST["latitude"];
	exit();
}

// Longitude check (must be more than 65)
if (!is_numeric($_POST["longitude"]) || floatval($_POST["longitude"]) <= 65) {
	echo "Error : Longitude must be more than 65. Given: " . $_POST["longitude"];
	exit();
}

if(!isStringNumber($_POST["demand"])){
	echo "Error : Check Off Take rice Value (Must be non-negative with up to 2 decimal places)";
	exit();
}

if(!preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])){
	echo "Error : FCI ID must contain only letters and numbers (no spaces or special characters)";
	exit();
}

$district = formatName($_POST["district"]);
$latitude = $_POST["latitude"];
$longitude = $_POST["longitude"];
$name = formatName($_POST["name"]);
$id = $_POST["id"];
$type = $_POST["type"];
$demand = $_POST["demand"];
$uniqueid = $_POST["uniqueid"];
$active = $_POST["active"];

$DCP = new DCP;
$DCP->setUniqueid($uniqueid);
$DCP->setDistrict($district);
$DCP->setLatitude($latitude);
$DCP->setLongitude($longitude);
$DCP->setName($name);
$DCP->setId($id);
$DCP->setType($type);
$DCP->setDemand($demand);
$DCP->setActive($active);

$query_check = $DCP->checkInsert($DCP);
$query_result = mysqli_query($con, $query_check);
$numrows = mysqli_num_rows($query_result);
if($numrows!=0){
	$row = mysqli_fetch_assoc($query_result);
	$uniqueid_check = $row["uniqueid"];
	if($uniqueid!=$uniqueid_check){
		echo "Error : in updating data as FCI id already exist ID: ".$id;
		echo "</br>";
		exit();
	}
}

$query = $DCP->update($DCP);
mysqli_query($con, $query);

mysqli_close($con);

echo "<script>window.location.href = '../DCP.php';</script>";


?>
<?php require('Fullui.php');  ?>