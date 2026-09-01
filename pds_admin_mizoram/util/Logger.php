<?php
function writeLog($message, $logDirectory = 'logs') {
    // Ensure log directory exists
    if (!file_exists($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }

    // Get current year, month, and day
    $year = date('Y');
    $month = date('m');
    $day = date('d');

    // Construct the directory structure (year/month)
    $yearDirectory = $logDirectory . DIRECTORY_SEPARATOR . $year;
    $monthDirectory = $yearDirectory . DIRECTORY_SEPARATOR . $month;

    if (!file_exists($yearDirectory)) {
        mkdir($yearDirectory, 0755, true);
    }

    if (!file_exists($monthDirectory)) {
        mkdir($monthDirectory, 0755, true);
    }

    // Define the log file path (year/month/day.log)
    $logFilePath = $monthDirectory . DIRECTORY_SEPARATOR . $day . '.log';

    // Format the log message with a timestamp, IP, and User
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN_IP';
    
    $user = 'UNKNOWN_USER';
    // We rely on the calling script to have already started the session
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    } elseif (isset($_SESSION['district_user'])) {
        $user = $_SESSION['district_user'];
    } elseif (isset($_SESSION['fci_user'])) {
        $user = $_SESSION['fci_user'];
    } elseif (isset($_SESSION['dfpd_user'])) {
        $user = $_SESSION['dfpd_user'];
    }

    $formattedMessage = "[$timestamp] [IP: $ip] [User: $user] $message" . PHP_EOL;

    // Write the log message to the file
    file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);
}

?>
