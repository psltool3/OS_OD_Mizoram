<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');
require('../util/Encryption.php');
require('../util/Logger.php');

header('Content-Type: application/json');

if (!SessionCheck()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

if (empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Admin credentials are required.']);
    exit;
}

$nonceValue = 'nonce_value';

$newusername = htmlspecialchars($_POST['newusername'] ?? '', ENT_QUOTES, 'UTF-8');
if (!preg_match('/^[a-zA-Z0-9_@\.]+$/', $newusername)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, underscores, dots and @.']);
    exit;
}

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
if (!preg_match($passwordPattern, $_POST['newpassword'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.']);
    exit;
}

$admin_username = $_POST['username'];
if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $admin_username)) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin username format.']);
    exit;
}

$Encryption = new Encryption();
$admin_password = $Encryption->decrypt($_POST['password'], $nonceValue);

$stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Error: Admin username does not exist.']);
    exit;
}

if (!password_verify($admin_password, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Error: Admin password is incorrect.']);
    exit;
}

$hashedPassword = password_hash($_POST['newpassword'], PASSWORD_DEFAULT);
$uid = uniqid();
$role = strtolower($_POST['district'] ?? 'admin');

$check_stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$check_stmt->bind_param("s", $newusername);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$numrows = $check_result->num_rows;
$check_stmt->close();

if ($numrows == 1) {
    echo json_encode(['success' => false, 'message' => 'Error: Username already exists.']);
    exit;
}

$insert_stmt = $con->prepare("INSERT INTO login (username, password, uid, role, verified, token, lastlogin, permission) VALUES (?, ?, ?, ?, '1', '', '', '')");
$insert_stmt->bind_param("ssss", $newusername, $hashedPassword, $uid, $role);
$insert_stmt->execute();
$insert_stmt->close();

mysqli_close($con);

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("User -> User Add -> " . $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost) . " | " . $newusername);

echo json_encode(['success' => true, 'message' => 'User created successfully!']);
exit;
?>
