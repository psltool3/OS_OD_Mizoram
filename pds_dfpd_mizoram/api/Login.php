<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/Security.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';
session_start();
require('../util/Logger.php');

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Something went wrong. Request denied.");
}

if ($_SESSION['captcha'] !=  $_POST['captchainput']){
	die("Please Check Captcha");
}

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(empty($row)){
 writeLog("Failed Login Attempt -> Username incorrect or does not exist for username: " . $person->getUsername());
 die("Invalid Credentials");
}

if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()) {
    $remaining_seconds = strtotime($row['locked_until']) - time();
    $remaining_minutes = ceil($remaining_seconds / 60);
    die("Error: Account is temporarily locked due to 5 failed attempts. Please try again in $remaining_minutes min.");
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
 if($row['role']=="dfpd"){
		session_regenerate_id(true);
		$count = 1 + $row['count'];
		$uniqueId = uniqid();
		$authToken = md5($uniqueId);
		$currentLoginTime = date("Y-m-d H:i:s");
		$queryUpdate = "UPDATE login SET token='$authToken',lastlogin='$currentLoginTime',count='$count',failed_attempts=0,locked_until=NULL WHERE username='".$person->getUsername()."'";
		mysqli_query($con,$queryUpdate);
		
		$_SESSION['user'] = $person->getUsername();
		$_SESSION['token'] = $authToken;
		
		writeLog("Successful Login Attempt for user: " . $person->getUsername());
		mysqli_close($con);
		 echo "<script>window.location.href = '../OptimisedDataAll.php';</script>";
    }
} 
else{
    $failed_attempts = $row['failed_attempts'] + 1;
    if ($failed_attempts >= 5) {
        $lock_query = "UPDATE login SET failed_attempts = $failed_attempts, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE username='".$person->getUsername()."'";
    } else {
        $lock_query = "UPDATE login SET failed_attempts = $failed_attempts WHERE username='".$person->getUsername()."'";
    }
    mysqli_query($con, $lock_query);

    writeLog("Failed Login Attempt -> Password incorrect for username: " . $person->getUsername());
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>