<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php');

if(!SessionCheck()){
	return;
}

require('Header.php');

$error = null;

foreach ($_POST as $key => $value) {
    // Check if the parameter starts with 'cost_' and is not empty
    if (substr($key, 0, 5) === 'cost_' && $value !== '' && $value !== null) {
        $value_temp = trim($value);
        if ($value_temp === '') {
            continue;
        }

        // Validate that value is a valid numeric value
        if (!is_numeric($value_temp)) {
            $error = "Cost must be a valid number: " . htmlspecialchars($value_temp, ENT_QUOTES, 'UTF-8');
            break;
        }

        $numeric_value = (float)$value_temp;
        if ($numeric_value < 0) {
            $error = "Cost cannot be negative: " . htmlspecialchars($value_temp, ENT_QUOTES, 'UTF-8');
            break;
        }

        // Extract the ID from the parameter name
        $id = substr($key, 5);
        $id = mysqli_real_escape_string($con, $id);

		$log_query = "SELECT year, month FROM optimised_table WHERE id='$id'";
		$log_result = mysqli_query($con, $log_query);
        $user_id = "";
        $user_id1 = "";
		if ($log_result && $row = $log_result->fetch_assoc()) {
			$user_id = $row['year'];
			$user_id1 = $row['month'];
		}

        // Update the optimised table where id equals the extracted ID
        $clean_cost = mysqli_real_escape_string($con, (string)$numeric_value);
        $sql = "UPDATE optimised_table SET cost = '$clean_cost' WHERE id = '$id'";
        
        $filteredPost = $_POST;
        unset($filteredPost['username'], $filteredPost['password']);
        writeLog("User -> Cost for leg2 Added -> " . $_SESSION['user'] . " | Requested JSON -> " . json_encode($filteredPost) . " | " . $user_id . " | " . $user_id1);

        if ($con->query($sql) !== TRUE) {
            $error = "Error updating record: " . $con->error;
            break;
        }
    }
}

if ($error !== null) {
    echo "<script>alert('Error: " . addslashes($error) . "'); window.location.href = '../Performa.php';</script>";
    exit;
} else {
    echo "<script>window.location.href = '../Performa.php';</script>";
    exit;
}
?>

<?php require('Fullui.php');  ?>