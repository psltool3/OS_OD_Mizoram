<?php
require('Connection.php');

$ip_address = "";

if (!empty($_SERVER['HTTP_CLIENT_IP']))   
  {
	$ip_address = $_SERVER['HTTP_CLIENT_IP'];
  }
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  
  {
	$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
else
  {
	$ip_address = $_SERVER['REMOTE_ADDR'];
  }

session_start();

$timeout_duration = 3600; // 1 hour
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
} else {
    $_SESSION['last_activity'] = time();
}

if(isset($_SESSION['user'])){
	$user = $_SESSION['user'];
	$token = $_SESSION['token'];
	$query = "SELECT * FROM login WHERE username='$user' AND token='$token'";
	$result = mysqli_query($con,$query);
	$numrows = mysqli_num_rows($result);
	
	if($numrows==0){
		header("Location:/OS_OD_Mizoram/pds_admin_mizoram/AdminLogin.html");
		exit();
	}
	$currentLoginTime = date("Y-m-d H:i:s");
	$queryUpdate = "UPDATE login SET lastlogin='$currentLoginTime' WHERE username='$user'";
	mysqli_query($con,$queryUpdate);
	
}
else{
	header("Location:/OS_OD_Mizoram/pds_admin_mizoram/AdminLogin.html");
}

?>
