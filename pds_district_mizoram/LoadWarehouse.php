<?php
// Disable timeouts (can run for several minutes)
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require('util/Connection.php');
require('structures/Warehouse.php');
require('util/SessionFunction.php');
require('util/SessionCheck.php');
require('util/Logger.php');
require('util/Security.php');
require('Header.php');

$session_district = $_SESSION['district_district'] ?? '';

function formatName($name) {
    if (!$name) return '';
    $name = ucwords(strtoupper($name));
    return trim($name);
}

function isValidCoordinate($value, $type) {
    if ($value === null || $value === '') return false;
    if (!is_numeric($value)) return false;
    $v = (float)$value;
    // The API uses 1 for dummy coordinates, so we allow any value > 0
    return $v > 0;
}

// ✅ Mizoram API endpoint
$apiUrl = 'https://epos.mizoram.gov.in/Metadata/api/metadata/mlsdemandmetadatanew';

// Dynamic Month Scan - start from current month and search backward up to 12 months
$targetMonth = (int)date('n');
$targetYear = (int)date('Y');
$apiResponse = null;
$success = false;

for ($i = 0; $i < 12; $i++) {
    $apiData = [
        'month' => (string)$targetMonth,
        'year'  => (string)$targetYear
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($apiData),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error    = curl_error($curl);
    curl_close($curl);

    if (!$error && $httpCode === 200) {
        $decoded = json_decode($response, true);
        if ($decoded && ($decoded['status'] ?? '') === '200' && !empty($decoded['data'])) {
            $apiResponse = $decoded;
            $success = true;
            break;
        }
    }

    // Go to previous month
    $targetMonth--;
    if ($targetMonth === 0) {
        $targetMonth = 12;
        $targetYear--;
    }
}

if (!$success || !$apiResponse) {
    echo "<div style='margin-left:250px; margin-top: 50px;'>Error: Failed to fetch valid warehouse data from API after scanning recent months.</div>\n";
    exit();
}

$warehouseData = $apiResponse['data'];

// Clear existing data for this specific district before pushing fresh data to prevent duplicates
$escaped_district = mysqli_real_escape_string($con, strtolower(trim($session_district)));
mysqli_query($con, "DELETE FROM warehouse WHERE LOWER(district) = '$escaped_district'");

$insertedCount = 0;
$errorCount    = 0;
$errorMessages = [];

foreach ($warehouseData as $data) {
    try {
        if (empty($data['id']) || empty($data['name']) || empty($data['district'])) {
            $errorCount++;
            continue;
        }

        $formattedDistrict = ucwords(strtolower(trim($data['district'])));
        if (strtolower($formattedDistrict) !== strtolower(trim($session_district))) {
            // Skip data not meant for the logged-in district
            continue;
        }

        $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : 0;
        $lon = isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : 0;

        $totalStorage = 0;
        if (isset($data['storage'])) {
            $val = trim($data['storage']);
            if (is_numeric($val)) {
                $totalStorage = (float)$val;
            } else {
                $totalStorage = 0;
            }
        }

        $isActive = $data['active'] ?? '1';
        $hasError = false;
        $errorReasons = [];

        // 1. Check coordinates
        if ($lat == 0 || $lon == 0 || !isValidCoordinate($lat, 'latitude') || !isValidCoordinate($lon, 'longitude')) {
            $hasError = true;
            $errorReasons[] = "Latitude or Longitude is 0 or invalid (Lat: $lat, Lon: $lon)";
        }

        // 2. Check invalid storage
        if ($totalStorage <= 0) {
            $hasError = true;
            $errorReasons[] = "Storage Capacity is 0 or negative ($totalStorage)";
        }

        // 3. If invalid, mark inactive and log error
        if ($hasError) {
            $isActive = '0';
            $errorCount++;
            $errorMessages[] = "Error: ID '{$data['id']}' ({$data['name']}): " . implode(', ', $errorReasons) . " -> Added to DB with Status: Inactive (0)";
        }

        $warehouse = new Warehouse;
        $warehouse->setDistrict(strtoupper(trim($data['district'] ?? '')));
        $warehouse->setName(formatName($data['name']));
        $warehouse->setId($data['id']);
        $warehouse->setWarehousetype($data['type'] ?? 'MLSP');
        $warehouse->setType('Motorable');
        $warehouse->setLatitude($lat);
        $warehouse->setLongitude($lon);
        $warehouse->setStorage($totalStorage);
        $warehouse->setUniqueid(substr(uniqid("WH_"), 0, 15));
        $warehouse->setActive($isActive);

        $insertQuery = $warehouse->insert($warehouse);
        if (mysqli_query($con, $insertQuery)) {
            $insertedCount++;
            $user = isset($_SESSION['user']) ? $_SESSION['user'] : 'SYSTEM';
            if(function_exists('writeLog')){
                writeLog("User -> " . $user . " | Warehouse loaded from API -> " . ($data['name'] ?? '') . " | District -> " . ($data['district'] ?? '') . " | Status -> " . $isActive);
            }
        } else {
            $errorCount++;
        }

    } catch (Exception $e) {
        $errorCount++;
        continue;
    }
}

mysqli_close($con);

echo "<div style='margin-left:250px; margin-top: 50px;'>";
echo "<h3>Data Load Complete</h3><br/>\n";

if (!empty($errorMessages)) {
    foreach ($errorMessages as $msg) {
        echo "<p style='color:orange;'>" . htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</p>\n";
    }
}

echo "<p>-------------------------</p>\n";
echo "<p>New records inserted : $insertedCount</p>\n";
echo "<p>Records with errors  : $errorCount</p>\n";
echo "</div>";

// Redirect to Warehouse page after completion
echo "<script type='text/javascript'>";
echo "setTimeout(function() {";
echo "window.location.href = 'Warehouse.php';";
echo "}, 3000);"; // Wait 3 seconds to show the summary
echo "</script>";
?>
