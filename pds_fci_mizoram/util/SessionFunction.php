<?php

function SessionCheck(){
	global $con;
	require_once(__DIR__ . '/Connection.php');
	if (session_status() === PHP_SESSION_NONE) {
		ini_set('session.gc_maxlifetime', 20000);
		ini_set('session.cookie_httponly', 1);
		ini_set('session.cookie_secure', 1);
		ini_set('session.cookie_samesite', 'Strict');
		session_set_cookie_params(20000);
		session_start();
	}

	if(isset($_SESSION['user'])){
		$user = $_SESSION['user'];
		$token = $_SESSION['token'];
		$query = "SELECT * FROM login WHERE username='$user' AND token='$token'";
		$result = mysqli_query($con,$query);
		$numrows = mysqli_num_rows($result);
		if($numrows==0){
			return false;
		}
		else{
			$currentLoginTime = date("Y-m-d H:i:s");
			$queryUpdate = "UPDATE login SET lastlogin='$currentLoginTime' WHERE username='$user'";
			mysqli_query($con,$queryUpdate);
			
			return true;
		}
	}
	else{
		return false;
	}
}

?>
