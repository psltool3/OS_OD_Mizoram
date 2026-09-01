<?php
require('../util/Connection.php');
require('../util/Encryption.php');

session_start();

$nonceValue = 'nonce_value';
$Encryption = new Encryption();

$username = $_POST['username'];
$oldpassword = $Encryption->decrypt($_POST["oldpassword"], $nonceValue);
$newpassword = $Encryption->decrypt($_POST["newpassword"], $nonceValue);
$confirmpassword = $Encryption->decrypt($_POST["confirmpassword"], $nonceValue);

if(empty($newpassword) || empty($confirmpassword) || empty($username) || empty($oldpassword)){
	echo "<script>alert('Error: All fields are required.'); window.history.back();</script>";
	return;
}
if($newpassword !== $confirmpassword){
	echo "<script>alert('Error: Both Passwords do not match.'); window.history.back();</script>";
	return;
}

$pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
if (!preg_match($pattern, $newpassword)) {
    echo "<script>alert('Error: Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and one special character.'); window.history.back();</script>";
    return;
}

$query = "SELECT * FROM login WHERE username='$username'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

if (empty($row)) {
    echo "<script>alert('Error: Invalid username or password.'); window.history.back();</script>";
    return;
}

if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()) {
    $remaining_seconds = strtotime($row['locked_until']) - time();
    $remaining_minutes = ceil($remaining_seconds / 60);
    echo "<script>alert('Error: Account is temporarily locked due to 5 failed attempts. Please try again in $remaining_minutes min.'); window.history.back();</script>";
    return;
}

if ($row['role'] !== 'admin') {
    echo "<script>alert('Error: Unauthorized role for this module.'); window.history.back();</script>";
    return;
}

$dbHashedPassword = $row['password'];
if (!password_verify($oldpassword, $dbHashedPassword)) {
    $failed_attempts = $row['failed_attempts'] + 1;
    if ($failed_attempts >= 5) {
        $lock_query = "UPDATE login SET failed_attempts = $failed_attempts, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE username='$username'";
    } else {
        $lock_query = "UPDATE login SET failed_attempts = $failed_attempts WHERE username='$username'";
    }
    mysqli_query($con, $lock_query);

    echo "<script>alert('Error: Old password is incorrect.'); window.history.back();</script>";
    return;
}

$historyQuery = "SELECT password_hash FROM password_history WHERE username='$username' ORDER BY changed_at DESC LIMIT 5";
$historyResult = mysqli_query($con, $historyQuery);
if ($historyResult) {
    while ($historyRow = mysqli_fetch_assoc($historyResult)) {
        if (password_verify($newpassword, $historyRow['password_hash'])) {
            echo "<script>alert('Error: You cannot use any of your previous 5 passwords.'); window.history.back();</script>";
            return;
        }
    }
}

$newHashedPassword = password_hash($newpassword, PASSWORD_DEFAULT);
$updateQuery = "UPDATE login SET password='$newHashedPassword', failed_attempts=0, locked_until=NULL WHERE username='$username'";
mysqli_query($con, $updateQuery);

$insertHistoryQuery = "INSERT INTO password_history (username, password_hash) VALUES ('$username', '$newHashedPassword')";
mysqli_query($con, $insertHistoryQuery);

$cleanupQuery = "DELETE FROM password_history WHERE username='$username' AND id NOT IN (
    SELECT id FROM (
        SELECT id FROM password_history WHERE username='$username' ORDER BY changed_at DESC LIMIT 5
    ) temp
)";
mysqli_query($con, $cleanupQuery);

mysqli_close($con);
echo "<script>alert('Password reset successful. Please login with your new password.'); window.location.href = '../AdminLogin.html';</script>";
?>