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

if ($row['role'] !== 'dfpd') {
    echo "<script>alert('Error: Unauthorized role for this module.'); window.history.back();</script>";
    return;
}

$dbHashedPassword = $row['password'];
if (!password_verify($oldpassword, $dbHashedPassword)) {
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
$updateQuery = "UPDATE login SET password='$newHashedPassword' WHERE username='$username'";
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
echo "<script>alert('Password reset successful. Please login with your new password.'); window.location.href = '../Login.html';</script>";
?>