<?php

require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Security.php');
require ('../util/Encryption.php');
require('../util/Logger.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

if(empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])){
    die("Something went wrong...");
}

function formatName($name) {
    if(preg_match('/[^a-zA-Z\s]/', $name)){
        echo "Error : Name contains invalid characters. Only letters and spaces are allowed.";
		exit();
    }
    $name = strtoupper($name);
    return trim($name);
}

if (!isset($_POST['name'], $_POST['district_code']) || !is_string($_POST['name']) || trim($_POST['name']) === '' || !is_string($_POST['district_code']) || !preg_match('/^[A-Za-z0-9]{1,50}$/D', $_POST['district_code'])) {
    die('Error : District name and an alphanumeric District Code (1-50 characters) are required.');
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

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
	$District = new District;
	$District->setId(uniqid());
	$District->setCode($_POST['district_code']);
	$District->setName(formatName($_POST['name']));

	$query = $District->check($District, $con);
	try {
		$result = mysqli_query($con, $query);
	} catch (mysqli_sql_exception $e) {
		die('Error : Unable to check district.');
	}
	if (!$result) { die('Error : Unable to check district.'); }
	$numrows = mysqli_num_rows($result);
	if($numrows>0){
		echo "Error : District name or code already exists";
		exit();
	}
	$query = $District->insert($District, $con);
	try {
		if (!mysqli_query($con, $query)) { die('Error : Unable to add district. Name or code may already exist.'); }
	} catch (mysqli_sql_exception $e) {
		die('Error : Unable to add district. Name or code may already exist.');
	}
	mysqli_close($con);

	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("User ->" ." District added ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));


	echo "<script>window.location.href = '../District.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>