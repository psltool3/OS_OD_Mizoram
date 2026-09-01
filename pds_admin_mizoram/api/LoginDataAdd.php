<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');
require ('../util/Encryption.php');
require('../util/Logger.php');


if(!SessionCheck()){
    return;
}

require('Header.php');

if(empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])){
    die("Something went wrong");
}

$nonceValue = 'nonce_value';

// Get the username and password from the POST data
$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

// Check if the session user matches the submitted username
// if($_SESSION['user']!=$person->getUsername()){
//     echo "User is logged in with a different username and password";
//     return;
// }

// if (strlen($_POST["newpassword"]) < '8' || strlen($_POST["newusername"]) < '5') {
//     echo "Password must be at least 8 characters long & Username must be at least 5 characters long ";
//     return;
// }

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
if (!preg_match($passwordPattern, $_POST["newpassword"])) {
    echo "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    return;
}

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));


$newusername = htmlspecialchars($_POST["newusername"], ENT_QUOTES, 'UTF-8');

// Ensure the new username doesn't contain special characters (optional)
if (!preg_match('/^[a-zA-Z0-9_@]+$/', $newusername)) {
    echo "Username can only contain letters, numbers, underscores and @.";
    return;
}

if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $person->getUsername())) {
    echo "Invalid admin username format.";
    return;
}

$admin_username = $person->getUsername();

// Query the database to get the stored hash for the username
$stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Check if the username exists and verify the password using password_verify
if ($row) {
    if (password_verify($person->getPassword(), $row['password'])) {
        // Password is correct
        // Now proceed with other logic
        $person = new Login;
        $person->setUsername($_POST["newusername"]);
        $person->setPassword($_POST["newpassword"]);
        $person->setRole($_POST["district"]);
        $uid = uniqid();
		
		$log_stmt = $con->prepare("SELECT username FROM login WHERE uid=?");
		$log_stmt->bind_param("s", $uid);
		$log_stmt->execute();
		$log_result = $log_stmt->get_result();
		if ($log_result && $log_row = $log_result->fetch_assoc()) {
			$log_name =  $log_row['username'];
		}
		$log_stmt->close();

        // Hash the new password before inserting it into the database
        $hashedPassword = password_hash($person->getPassword(), PASSWORD_DEFAULT);

        // Check if the new username already exists
        $check_stmt = $con->prepare("SELECT * FROM login WHERE username=?");
        $new_username = $person->getUsername();
        $check_stmt->bind_param("s", $new_username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $numrows = $check_result->num_rows;
        $check_stmt->close();

        if($numrows == 1){
            echo "Error : Username already exists";
        } else {
            // Insert the new user with the hashed password
            $insert_stmt = $con->prepare("INSERT INTO login (username, password, uid, role, verified) VALUES (?, ?, ?, ?, '1')");
            $role = strtolower($person->getRole());
            $insert_stmt->bind_param("ssss", $new_username, $hashedPassword, $uid, $role);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            mysqli_close($con);
			
			$filteredPost = $_POST;
			unset($filteredPost['username'], $filteredPost['password']);
			writeLog("User ->" ." User Add ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost). " | " . $person->getUsername());
            echo "<script>window.location.href = '../Userdata.php';</script>";
        }

    } else {
        // Password is incorrect
        echo "Error : Password is incorrect";
        return;
    }
} else {
    // Username doesn't exist
    echo "Error : Username does not exist";
    return;
}
?>
<?php require('Fullui.php'); ?>
