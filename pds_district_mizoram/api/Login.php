<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/Encryption.php');
require('../util/Security.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
require('../util/Logger.php');
}

if (!isset($_SESSION['captcha']) || !isset($_SESSION['csrf_token'])) {
    die("Sowething went wrong.");
}

if(empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])){
    die("Something went wrong...");
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  die("Something went wrong. Request denied.");
}

if (empty($_POST['captchainput']) ||$_SESSION['captcha'] !==  $_POST['captchainput']){
	unset($_SESSION['captcha']);
  die("Please Check Captcha");
}

$person = new Login;
$username = $_POST["username"];

if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $username)) {
    writeLog("Failed Login Attempt -> Invalid username format: " . $username);
    die("Invalid username format.");
}
$person->setUsername($username);
$nonceValue = 'nonce_value';

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

$stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (empty($row)) {
	writeLog("Failed Login Attempt -> Username incorrect or does not exist for username: " . $person->getUsername());
	die("Password or Username is incorrect");
}

if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()) {
    $remaining_seconds = strtotime($row['locked_until']) - time();
    $remaining_minutes = ceil($remaining_seconds / 60);
    die("Error: Account is temporarily locked due to 5 failed attempts. Please try again in $remaining_minutes min.");
}

if ($row['role'] == 'admin') {
		echo "Error: Admins are not allowed to log in here.";
		exit;
}

if ($row["verified"] == 0) {
		writeLog("Failed Login Attempt -> Account needs verification for username: " . $person->getUsername());
		echo "Error: Your account needs to be verified.";
		exit;
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    session_regenerate_id(true);
	$count = 1 + $row['count'];
	$uniqueId = uniqid();
	$authToken = md5($uniqueId);
	$currentLoginTime = date("Y-m-d H:i:s");
	
	$queryUpdate = $con->prepare("UPDATE login SET token=?, lastlogin=?, count=?, failed_attempts=0, locked_until=NULL WHERE username=?");
	$queryUpdate->bind_param("ssis", $authToken, $currentLoginTime, $count, $username);
	$queryUpdate->execute();
	$queryUpdate->close();

	$_SESSION['district_user'] = $person->getUsername();
	$_SESSION['district_password'] = $person->getPassword();
	$_SESSION['district_district'] = $row["role"];
	$_SESSION['district_token'] = $authToken;
	$_SESSION['token'] = $authToken;
		
	writeLog("Successful Login -> User logged in: " . $person->getUsername());
	mysqli_close($con);
	echo "<script>window.location.href = '../DistrictView.php';</script>";
} 
else{
    $failed_attempts = $row['failed_attempts'] + 1;
    if ($failed_attempts >= 5) {
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE username=?");
    } else {
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ? WHERE username=?");
    }
    $lock_query->bind_param("is", $failed_attempts, $username);
    $lock_query->execute();
    $lock_query->close();

    writeLog("Failed Login Attempt -> Password incorrect for username: " . $person->getUsername());
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>