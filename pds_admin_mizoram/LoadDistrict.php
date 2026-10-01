<?php
// Disable timeouts (can run for several minutes)
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require('util/Connection.php');
require('structures/District.php');
require('util/SessionFunction.php');
require('util/SessionCheck.php');
require('util/Logger.php');
require('util/Security.php');
require('Header.php');

function formatName($name) {
    if (!$name) return '';

    $name = strtoupper(trim($name));
    return $name;
}

// ✅ Mizoram API endpoint
$apiUrl = 'https://epos.mizoram.gov.in/Metadata/api/metadata/Districtlist';

// Initialize cURL (no timeout)
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_CONNECTTIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_PROXY          => ''
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error    = curl_error($curl);
curl_close($curl);

if ($error) {
    echo "Error connecting to API: " . htmlspecialchars($error, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "\n";
    exit();
}
if ($httpCode !== 200) {
    echo "API returned error code: " . $httpCode . "\n";
    exit();
}

$apiResponse = json_decode($response, true);
if (!$apiResponse || ($apiResponse['code'] ?? null) !== 'Success-100') {
    echo "Invalid API response or API returned error.\n";
    exit();
}
if (empty($apiResponse['districtlist'])) {
    echo "No district data found.\n";
    exit();
}

$districtData = $apiResponse['districtlist'];

// Clear existing data before pushing fresh data to prevent duplicates
mysqli_query($con, "TRUNCATE TABLE districts");

$totalRecords  = count($districtData);
$insertedCount = 0;
$errorCount    = 0;

foreach ($districtData as $data) {
    try {
        if (empty($data['district_code']) || empty($data['district_name'])) {
            $errorCount++;
            continue;
        }

        $district = new District;
        $district->setId($data['district_code']);
        $district->setName(formatName($data['district_name']));

        $insertQuery = $district->insert($district);
        if (mysqli_query($con, $insertQuery)) {
            $insertedCount++;
            $user = isset($_SESSION['user']) ? $_SESSION['user'] : 'SYSTEM';
            if(function_exists('writeLog')){
                writeLog("User -> " . $user . " | District loaded from API -> " . $data['district_name']);
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

// Plain text summary (no scripts)
echo "<div style='margin-left:250px; margin-top: 50px;'>";
echo "<h3>Data Load Complete</h3><br/>\n";
echo "-------------------------<br/>\n";
echo "New records inserted : $insertedCount<br/>\n";
echo "Records with errors  : $errorCount<br/>\n";
echo "-------------------------<br/>\n";
echo "Source: Districtlist<br/>\n";
echo "</div>";

// Redirect to District page after completion
echo "<script type='text/javascript'>";
echo "setTimeout(function() {";
echo "window.location.href = 'District.php';";
echo "}, 3000);"; // Wait 3 seconds to show the summary
echo "</script>";
?>
