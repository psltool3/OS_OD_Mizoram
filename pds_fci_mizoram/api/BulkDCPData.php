<?php
require_once('../util/SessionFunction.php');
if(!SessionCheck()){
    die('Unauthorized');
}

require('../util/Connection.php');
require('../structures/DCP.php');
require('../util/SessionFunction.php');
ini_set('max_execution_time', 3000);

require('Header.php');

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
		array_push($districts,$row["name"]);
	}
}


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
    return is_numeric($stringValue) && preg_match('/^\d+(\.\d+)?$/', trim($stringValue)) && floatval($stringValue) >= 0;
}

$redirect = 1;

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
					echo "Error : You have modified Template Header, please check";
					exit();
				}
				if(!isValidCoordinate($column[$latitude],'latitude') or !isValidCoordinate($column[$longitude],'longitude')){
					echo "Error : Check Latitude and Longitude Value Latitude: ".$column[$latitude]." Longitude: ".$column[$longitude];
					echo "</br>";
					$redirect = 0;
				}
				if(!isStringNumber($column[$demand])){
					echo "Error : Check Demand Value: ".$column[$demand];
					echo "</br>";
					$redirect = 0;
				}

				if(!in_array(strtoupper(trim($column[$district])), $districts)){
					echo "Error : Check District Name: ".$column[$district];
					echo "</br>";
					$redirect = 0;
				}
				if(strtoupper(trim($column[$type])) !== 'FCI'){
					echo "Error : Check Type (must be FCI): ".$column[$type];
					echo "</br>";
					$redirect = 0;
				}
				if(!($column[$active]==0 || $column[$active]==1)){
					echo "Error : Check value of active/inactive column: ".$column[$active];
					echo "</br>";
					$redirect = 0;
				}
				if (
					!isset($column[$id]) ||
					!preg_match('/^[A-Za-z0-9]+$/', $column[$id])
				) {
					echo "Error: FCI ID should not contain spaces or any special characters: " . ($column[$id] ?? 'Missing');
					echo "<br>";
					$redirect = 0;
				}
				
				if (!is_numeric($column[$latitude]) || $column[$latitude] <= 0 || $column[$latitude] >= 45) {
					echo "Error : Latitude must be between 0 and 45. Given: " . $column[$latitude];
					echo "</br>";
					$redirect = 0;
				}

				// Longitude check (must be more than 65)
				if (!is_numeric($column[$longitude]) || $column[$longitude] <= 65) {
					echo "Error : Longitude must be more than 65. Given: " . $column[$longitude];
					echo "</br>";
					$redirect = 0;
				}	
			}
			else{
				for($j=0;$j<count($column);$j++){
					switch($column[$j]){
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
	}
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file";
}

if($redirect == 0){
	exit();
}

try{
	//if (isset($_POST["submit"])){
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
						echo "Error : You have modified Template Header, please check";
						exit();
					}
					$DCP = new DCP;
					$uniqueid = uniqid("DCP_",);
					$DCP->setUniqueid(substr($uniqueid,0,15));
					$DCP->setDistrict(strtoupper(trim($column[$district])));
					$DCP->setLatitude($column[$latitude]);
					$DCP->setLongitude($column[$longitude]);
					$DCP->setName($column[$name]);
					$DCP->setId($column[$id]);
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
							$uniqueid = uniqid("DCP_",);
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
						echo "Error : FCI with id ".$DCP->getId()." Already Exist</br>";
						$redirect = 2;
					}
				}
					
				else{
					for($j=0;$j<count($column);$j++){
						switch($column[$j]){
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
			if($redirect==1){
				echo "<script>window.location.href = '../DCP.php';</script>";
			}
		}
	//}
	//else{
		//echo "Error Please Select .csv file";
	//}
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file";
}
?>
<?php
require_once('../util/SessionFunction.php');
if(!SessionCheck()){
    die('Unauthorized');
}
 require('Fullui.php');  ?>