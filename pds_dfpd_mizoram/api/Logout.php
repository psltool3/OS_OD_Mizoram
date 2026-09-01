<?php
session_start();
require('../util/Logger.php');

if (isset($_SESSION['user'])) {
    writeLog("Successful Logout -> User logged out: " . $_SESSION['user']);
}

// Unset all session variables
$_SESSION = array();

// If there's a session cookie, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to login page
header("Location: ../Login.html");
exit;
?>