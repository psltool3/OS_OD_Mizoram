<?php
require('../util/Connection.php');
require('../structures/DCP.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
ini_set('max_execution_time', 3000);
require('../util/Security.php');
require('../util/Encryption.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"] ?? "");
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"] ?? "", $nonceValue));

if($_SESSION['user'] != $person->getUsername()){
	echo "Error: User is logged in with different username and password";
	require('Fullui.php');
	exit();
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(empty($row) || !password_verify($person->getPassword(), $row['password'])){
	echo "Error : Password or Username is incorrect";
	require('Fullui.php');
	exit();
}

$mapData = [
    "District" => "district",
    "Name of FCI" => "name",
    "FCI ID" => "id",
    "Type" => "type",
    "Latitude" => "latitude",
    "Longitude" => "longitude",
    "Offered Quantity FRice (Qtl)" => "demand",
	"Active/Not-Active" => "active"
];

// Reverse mapping
$reverseMapData = array_flip($mapData);

$districts = [];
$query = "SELECT name FROM districts WHERE 1";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);
if($numrows>0){
	while($row=mysqli_fetch_assoc($result)){
		array_push($districts, strtoupper(trim($row["name"])));
	}
}

function formatName($name) {
	$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
    $name = strtoupper($name);
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    if (!is_numeric($value)) {
        return false;
    }
    $coordinate = floatval($value);
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
    return is_numeric($stringValue) && preg_match('/^\d+(\.\d+)?$/', trim($stringValue)) && floatval($stringValue) >= 0;
}

function filterData(&$str){ 
    $str = str_replace("\t", "", $str);
}

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] != 0 || $_FILES["file"]["size"] <= 0) {
    echo "Error : Please select a valid .csv file";
    require('Fullui.php');
    exit();
}

$redirect = 1;

try {
	$fileName = $_FILES["file"]["tmp_name"];
	$file = fopen($fileName, "r");
	$i = 0;
	$district = -1;
	$name = -1;
	$id = -1;
	$type = -1;
	$demand = -1;
	$longitude = -1;
	$latitude = -1;
	$active = -1;
	while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
		if($i>0){
			if($district<0 or $name<0 or $id<0 or $type<0 or $demand<0 or $latitude<0 or $longitude<0 or $active<0){
				echo "Error : You have modified Template Header, please check<br>";
				$redirect = 0;
				break;
			}
			if(!isValidCoordinate($column[$latitude],'latitude') or !isValidCoordinate($column[$longitude],'longitude')){
				echo "Error : Check Latitude and Longitude Value Latitude: ".htmlspecialchars($column[$latitude])." Longitude: ".htmlspecialchars($column[$longitude])."<br>";
				$redirect = 0;
			}
			if(!isStringNumber($column[$demand])){
				echo "Error : Check Demand Value: ".htmlspecialchars($column[$demand])."<br>";
				$redirect = 0;
			}

			if(!in_array(strtoupper(trim($column[$district])), $districts)){
				echo "Error : Check District Name: ".htmlspecialchars($column[$district])."<br>";
				$redirect = 0;
			}
			if(strtoupper(trim($column[$type])) !== 'FCI'){
				echo "Error : Check Type (must be FCI): ".htmlspecialchars($column[$type])."<br>";
				$redirect = 0;
			}
			if(!($column[$active] === '0' || $column[$active] === '1' || $column[$active] === 0 || $column[$active] === 1)){
				echo "Error : Check value of active/inactive column: ".htmlspecialchars($column[$active])."<br>";
				$redirect = 0;
			}
			if (
				!isset($column[$id]) ||
				!preg_match('/^[A-Za-z0-9]+$/', trim($column[$id]))
			) {
				echo "Error: FCI ID should not contain spaces or any special characters: " . htmlspecialchars($column[$id] ?? 'Missing') . "<br>";
				$redirect = 0;
			}
			
			if (!is_numeric($column[$latitude]) || floatval($column[$latitude]) <= 0 || floatval($column[$latitude]) >= 45) {
				echo "Error : Latitude must be between 0 and 45. Given: " . htmlspecialchars($column[$latitude]) . "<br>";
				$redirect = 0;
			}

			if (!is_numeric($column[$longitude]) || floatval($column[$longitude]) <= 65) {
				echo "Error : Longitude must be more than 65. Given: " . htmlspecialchars($column[$longitude]) . "<br>";
				$redirect = 0;
			}	
		}
		else{
			for($j=0;$j<count($column);$j++){
				$trimmed = trim($column[$j]);
				switch($trimmed){
					case $reverseMapData["district"]:
						$district = $j;
						break;
					case $reverseMapData["latitude"]:
						$latitude = $j;
						break;
					case $reverseMapData["longitude"]:
						$longitude = $j;
						break;
					case $reverseMapData["name"]:
						$name = $j;
						break;
					case $reverseMapData["id"]:
						$id = $j;
						break;
					case $reverseMapData["type"]:
						$type = $j;
						break;
					case $reverseMapData["demand"]:
						$demand = $j;
						break;
					case $reverseMapData["active"]:
						$active = $j;
						break;
				}
			}
		}
		$i = $i+1;
	}
	fclose($file);
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file<br>";
	$redirect = 0;
}

if($redirect == 0){
	require('Fullui.php');
	exit();
}

try{
	$fileName = $_FILES["file"]["tmp_name"];
	if ($_FILES["file"]["size"] > 0) {
		$file = fopen($fileName, "r");
		$i = 0;
		$district = -1;
		$name = -1;
		$id = -1;
		$type = -1;
		$demand = -1;
		$longitude = -1;
		$latitude = -1;
		$active = -1;
		while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
			if($i>0){
				if($district<0 or $name<0 or $id<0 or $type<0 or $demand<0 or $latitude<0 or $longitude<0 or $active<0){
					echo "Error : You have modified Template Header, please check<br>";
					$redirect = 0;
					break;
				}
				$DCP = new DCP;
				$uniqueid = uniqid("DCP_");
				$DCP->setUniqueid(substr($uniqueid,0,15));
				$DCP->setDistrict(strtoupper(trim($column[$district])));
				$DCP->setLatitude($column[$latitude]);
				$DCP->setLongitude($column[$longitude]);
				$DCP->setName($column[$name]);
				$DCP->setId(trim($column[$id]));
				$DCP->setType("FCI");
				$DCP->setDemand($column[$demand]);
				$DCP->setActive($column[$active]);
				while(true){
					$query_check = $DCP->check($DCP);
					$query_result = mysqli_query($con, $query_check);
					$numrows = mysqli_num_rows($query_result);
					if($numrows==0){
						break;
					}
					else{
						$uniqueid = uniqid("DCP_");
						$DCP->setUniqueid(substr($uniqueid,0,15));
					}
				}
				$query_insert_check = $DCP->checkInsert($DCP);
				$query_insert_result = mysqli_query($con, $query_insert_check);
				$numrows_insert = mysqli_num_rows($query_insert_result);
				if($numrows_insert==0){
					$query_add = $DCP->insert($DCP);
					mysqli_query($con, $query_add);
				}
				else{
					echo "Error : FCI with id ".htmlspecialchars($DCP->getId())." Already Exist<br>";
					$redirect = 2;
				}
			}
				
			else{
				for($j=0;$j<count($column);$j++){
					$trimmed = trim($column[$j]);
					switch($trimmed){
						case $reverseMapData["district"]:
							$district = $j;
							break;
						case $reverseMapData["latitude"]:
							$latitude = $j;
							break;
						case $reverseMapData["longitude"]:
							$longitude = $j;
							break;
						case $reverseMapData["name"]:
							$name = $j;
							break;
						case $reverseMapData["id"]:
							$id = $j;
							break;
						case $reverseMapData["type"]:
							$type = $j;
							break;
						case $reverseMapData["demand"]:
							$demand = $j;
							break;
						case $reverseMapData["active"]:
							$active = $j;
							break;
					}
				}
			}
			$i = $i+1;
		}
		fclose($file);
		if($redirect==1){
			echo "<script>window.location.href = '../DCP.php';</script>";
			exit();
		}
	}
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file<br>";
}

require('Fullui.php');
?>