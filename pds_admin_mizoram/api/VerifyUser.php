<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Security.php');
require('../util/Encryption.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

// Fetch the user based on the username
$query = "SELECT * FROM login WHERE username='" . $person->getUsername() . "'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);

if ($numrows == 0) {
    echo "Error : Username is incorrect";
    return;
}

// Fetch the hashed password from the database
$row = mysqli_fetch_assoc($result);
$hashedPassword = $row['password'];

// Verify the plain-text password with the hashed password
if (!password_verify($person->getPassword(), $hashedPassword)) {
    echo "Error : Password is incorrect";
    return;
}

$uid = $_POST["uid"];
$query = "UPDATE login SET verified='1' WHERE uid='$uid'";
mysqli_query($con, $query);
mysqli_close($con);

echo "<script>window.location.href = '../Userdata.php';</script>";

?>
<?php require('Fullui.php');  ?>