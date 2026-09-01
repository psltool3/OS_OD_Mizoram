<?php

session_start();
require('../util/Logger.php');
if (isset($_SESSION['district_user'])) {
    writeLog("Successful Logout -> User logged out: " . $_SESSION['district_user']);
}
$_SESSION['district_name'] = null;
$_SESSION['district_user'] = null;
header("Location:../Login.html");

?>