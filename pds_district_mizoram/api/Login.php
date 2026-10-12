<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/Encryption.php');
require('../util/Security.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../util/Logger.php');

$max_attempts = 5;
$lockout_minutes = 20;

if (!isset($_SESSION['captcha']) || !isset($_SESSION['csrf_token'])) {
    echo "<script>alert('Something went wrong. Please try again.'); window.location.href = '../Login.html';</script>";
    exit;
}

if(empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])){
    echo "<script>alert('Something went wrong. All fields are required.'); window.location.href = '../Login.html';</script>";
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo "<script>alert('Something went wrong. Request denied.'); window.location.href = '../Login.html';</script>";
    exit;
}

if (empty($_POST['captchainput']) ||$_SESSION['captcha'] !==  $_POST['captchainput']){
	unset($_SESSION['captcha']);
    echo "<script>alert('Please Check Captcha'); window.location.href = '../Login.html';</script>";
    exit;
}

$person = new Login;
$username = $_POST["username"];

if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $username)) {
    writeLog("Failed Login Attempt -> Invalid username format: " . $username);
    echo "<script>alert('Invalid username format.'); window.location.href = '../Login.html';</script>";
    exit;
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
	echo "<script>alert('Error: Password or Username is incorrect'); window.location.href = '../Login.html';</script>";
    exit;
}

// Check if account is temporarily locked
if ($row['locked_until'] !== null) {
    $lockedTime = strtotime($row['locked_until']);
    if ($lockedTime > time()) {
        $remaining_seconds = $lockedTime - time();
        $remaining_minutes = ceil($remaining_seconds / 60);
        writeLog("Locked Account Login Blocked -> User: " . $person->getUsername() . " | Remaining: $remaining_minutes min");
        echo "<script>alert('Error: Account is temporarily locked due to multiple failed attempts. Please try again in $remaining_minutes min.'); window.location.href = '../Login.html';</script>";
        exit;
    } else {
        // Lockout period expired: reset failed_attempts and clear locked_until
        $reset_lock = $con->prepare("UPDATE login SET failed_attempts = 0, locked_until = NULL WHERE username = ?");
        $reset_lock->bind_param("s", $username);
        $reset_lock->execute();
        $reset_lock->close();
        $row['failed_attempts'] = 0;
        $row['locked_until'] = null;
    }
}

if ($row['role'] == 'admin' || $row['role'] == 'fci' || $row['role'] == 'dfpd') {
    writeLog("Failed Login Attempt -> Unauthorized role for district module: " . $person->getUsername());
    echo "<script>alert('Error: Admins/FCI/DFPD are not allowed to log in here.'); window.location.href = '../Login.html';</script>";
    exit;
}

if ($row["verified"] == 0) {
	writeLog("Failed Login Attempt -> Account needs verification for username: " . $person->getUsername());
	echo "<script>alert('Error: Your account needs to be verified.'); window.location.href = '../Login.html';</script>";
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
	echo "<script>window.location.href = '../Home.php';</script>";
    exit;
} 
else{
    $failed_attempts = $row['failed_attempts'] + 1;
    if ($failed_attempts >= $max_attempts) {
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 20 MINUTE) WHERE username=?");
        $lock_query->bind_param("is", $failed_attempts, $username);
        $lock_query->execute();
        $lock_query->close();

        writeLog("Account Locked -> Max failed login attempts ($failed_attempts) reached for username: " . $person->getUsername());
        echo "<script>alert('Error: Account has been temporarily locked for 20 minutes due to $failed_attempts consecutive failed login attempts.'); window.location.href = '../Login.html';</script>";
        exit;
    } else {
        $remaining = $max_attempts - $failed_attempts;
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ? WHERE username=?");
        $lock_query->bind_param("is", $failed_attempts, $username);
        $lock_query->execute();
        $lock_query->close();

        writeLog("Failed Login Attempt -> Password incorrect for username: " . $person->getUsername() . " (Attempt $failed_attempts of $max_attempts)");
        echo "<script>alert('Error: Password or Username is incorrect. Attempts remaining: $remaining'); window.location.href = '../Login.html';</script>";
        exit;
    }
}

?>
<?php require('Fullui.php');  ?>