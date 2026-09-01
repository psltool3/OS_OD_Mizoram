<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
require('../util/Security.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

function generateRandomId($length = 10) {
    // Generate random bytes
    $bytes = random_bytes(ceil($length / 2));
    
    // Convert random bytes to hexadecimal string
    $randomId = substr(bin2hex($bytes), 0, $length);

    return $randomId;
}

if(!SessionCheck()){
	return;
}

require('Header.php');


$person = new Login;
$username = $_POST["username"];

if (!preg_match('/^[a-zA-Z0-9_@\.]{1,50}$/', $username)) {
    die("Invalid username format.");
}
$person->setUsername($username);

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

$stmt = $con->prepare("SELECT * FROM login WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    // Sanitize the message payload to mitigate XSS
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    // Ensure message length is within limits (e.g., 500 chars limit)
    if (strlen($message) > 500) {
        $message = substr($message, 0, 500);
    }
    
    $uniqueid = $_POST['uniqueid'];
    $date = date('Y-m-d H:i:s');

    $insert_stmt = $con->prepare("INSERT INTO user_message (id,user_id,message,date,acknowledged) VALUES (?,?,?,?,'no')");

    if($uniqueid=="all"){
        $select_query = "SELECT uid FROM login WHERE role!='admin'";
        $result = mysqli_query($con,$select_query);
        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $uid = $row['uid'];
                $id = generateRandomId(10);
                $insert_stmt->bind_param("ssss", $id, $uid, $message, $date);
                $insert_stmt->execute();
            }
        }
    }
    else{
        $uids = preg_split('/[,_\-]+/', $uniqueid);
        foreach($uids as $uid){
            $uid = trim($uid);
            if($uid !== ''){
                $id = generateRandomId(10);
                $insert_stmt->bind_param("ssss", $id, $uid, $message, $date);
                $insert_stmt->execute();
            }
        }
    }
    $insert_stmt->close();


    $log_name = '';
    if ($uniqueid == "all") {
        $log_name = "All Users";
    } else {
        $uids = preg_split('/[,_\-]+/', $uniqueid);
        $usernames = [];
        $log_stmt = $con->prepare("SELECT username FROM login WHERE uid=?");
        foreach($uids as $uid){
            $uid = trim($uid);
            if($uid !== ''){
                $log_stmt->bind_param("s", $uid);
                $log_stmt->execute();
                $log_result = $log_stmt->get_result();
                if ($log_result && $row = $log_result->fetch_assoc()) {
                    $usernames[] = $row['username'];
                }
            }
        }
        $log_stmt->close();
        $log_name = implode(', ', $usernames);
    }

    $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("User ->" ." Send  Message ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost). " | " . $log_name);

    echo "<script>window.location.href = '../SendMessage.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}
?>
<?php require('Fullui.php');  ?>