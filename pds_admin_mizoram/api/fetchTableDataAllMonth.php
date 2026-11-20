<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
header('Content-Type: application/json');
if (!SessionCheck()) {
    http_response_code(401);
    echo json_encode(array('data' => array(), 'message' => 'Unable to check L2. You can still continue with L1.'));
    exit;
}
$response = array('data' => array(), 'message' => 'Unable to check L2 for the selected period. You can still continue with L1.');
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$months = array('jan', 'feb', 'march', 'april', 'may', 'june', 'july', 'aug', 'sept', 'oct', 'nov', 'dec');
if (!is_string($month) || !in_array($month, $months, true) || !is_string($year) || !preg_match('/^[0-9]{4}$/D', $year)) {
    $response['message'] = 'Invalid month or year for the L2 check. You can still continue with L1.';
    echo json_encode($response);
    exit;
}
try {
    $stmt = mysqli_prepare($con, 'SELECT id, month, year, applicable, last_updated FROM optimised_table WHERE month=? AND year=? ORDER BY last_updated DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $month, $year);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                if ($row) {
                    $response['data'][] = $row;
                    $response['message'] = 'An L2 run was found for ' . $month . ' ' . $year . ' (updated ' . $row['last_updated'] . '). Please check that L2 is for the correct period. You can still continue with L1.';
                } else {
                    $response['message'] = 'No L2 run was found for ' . $month . ' ' . $year . '. You can still continue with L1.';
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    // Lookup failures remain advisory.
}
echo json_encode($response);
