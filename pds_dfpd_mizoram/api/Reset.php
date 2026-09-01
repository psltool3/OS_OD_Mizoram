<?php
require('../util/Connection.php');
require('../util/Encryption.php');

session_start();

$nonceValue = 'nonce_value';
$Encryption = new Encryption();

$username = $_POST['username'];

if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $username)) {
    echo "<script>alert('Error: Invalid username format.'); window.history.back();</script>";
    return;
}

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

$stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

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

if ($row['role'] !== 'dfpd') {
    echo "<script>alert('Error: Unauthorized role for this module.'); window.history.back();</script>";
    return;
}

$dbHashedPassword = $row['password'];
if (!password_verify($oldpassword, $dbHashedPassword)) {
    $failed_attempts = $row['failed_attempts'] + 1;
    if ($failed_attempts >= 5) {
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE username=?");
    } else {
        $lock_query = $con->prepare("UPDATE login SET failed_attempts = ? WHERE username=?");
    }
    $lock_query->bind_param("is", $failed_attempts, $username);
    $lock_query->execute();
    $lock_query->close();

    echo "<script>alert('Error: Old password is incorrect.'); window.history.back();</script>";
    return;
}

$historyStmt = $con->prepare("SELECT password_hash FROM password_history WHERE username=? ORDER BY changed_at DESC LIMIT 5");
$historyStmt->bind_param("s", $username);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
if ($historyResult) {
    while ($historyRow = $historyResult->fetch_assoc()) {
        if (password_verify($newpassword, $historyRow['password_hash'])) {
            echo "<script>alert('Error: You cannot use any of your previous 5 passwords.'); window.history.back();</script>";
            return;
        }
    }
}
$historyStmt->close();

$newHashedPassword = password_hash($newpassword, PASSWORD_DEFAULT);
$updateStmt = $con->prepare("UPDATE login SET password=?, failed_attempts=0, locked_until=NULL WHERE username=?");
$updateStmt->bind_param("ss", $newHashedPassword, $username);
$updateStmt->execute();
$updateStmt->close();

$insertHistoryStmt = $con->prepare("INSERT INTO password_history (username, password_hash) VALUES (?, ?)");
$insertHistoryStmt->bind_param("ss", $username, $newHashedPassword);
$insertHistoryStmt->execute();
$insertHistoryStmt->close();

$cleanupStmt = $con->prepare("DELETE FROM password_history WHERE username=? AND id NOT IN (SELECT id FROM (SELECT id FROM password_history WHERE username=? ORDER BY changed_at DESC LIMIT 5) temp)");
$cleanupStmt->bind_param("ss", $username, $username);
$cleanupStmt->execute();
$cleanupStmt->close();

mysqli_close($con);
echo "<script>alert('Password reset successful. Please login with your new password.'); window.location.href = '../Login.html';</script>";
?>