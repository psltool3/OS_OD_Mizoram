<?php

session_start();
require('../util/Logger.php');
if (isset($_SESSION['user'])) {
    writeLog("Successful Logout -> User logged out: " . $_SESSION['user']);
}
$_SESSION['name'] = null;
$_SESSION['user'] = null;
header("Location:../AdminLogin.html");

?>