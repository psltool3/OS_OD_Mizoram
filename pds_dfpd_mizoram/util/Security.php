<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script !== 'Login.php') {
        if (!isset($_POST['form_nonce']) || !isset($_SESSION['form_nonces']) || !in_array($_POST['form_nonce'], $_SESSION['form_nonces'])) {
            die("Error: Invalid or expired form submission (Nonce verification failed).");
        }
        $nonce_key = array_search($_POST['form_nonce'], $_SESSION['form_nonces']);
        if ($nonce_key !== false) {
            unset($_SESSION['form_nonces'][$nonce_key]);
        }
    }
}

// Function to sanitize and escape HTML characters
function escapeHTML($input) {
    return htmlspecialchars(strip_tags($input, '<b><i><u><strong><em><ul><ol><li>'), ENT_QUOTES, 'UTF-8');
}

/*function whitelistInput($input) {
    // Define a whitelist of allowed characters for alphanumeric and spaces
	$allowedCharacters = "/^[a-zA-Z0-9@\.\s\-\_\#\$]+$/";

    // Check if the input matches the alphanumeric and spaces whitelist
    return preg_match($allowedCharacters, $input) ? $input : false;
}*/

function whitelistInput($input) {
    // Define a whitelist of allowed characters for alphanumeric and spaces
	$allowedCharacters = "/[^a-zA-Z0-9@\.\s\-_#\$]+/";
    
    // Remove disallowed characters from the input
    $sanitizedInput = preg_replace($allowedCharacters, "", $input);

    // Return the sanitized input
    return $sanitizedInput;;
}


function removeWhiteSpace($string){
	$clean_string = preg_replace('/\s+/u', ' ', $string);
	return $clean_string;
}

function validateAndDecode($input) {
    // Decode HTML entities and URL encoding
    $decodedInput = urldecode(html_entity_decode($input, ENT_QUOTES, 'UTF-8'));
    
    // Check if input is not empty, strictly allowing '0' and 0
    return ($decodedInput !== '' && $decodedInput !== null && $decodedInput !== false) ? $decodedInput : false;
}

// Apply positive input validation to all elements in $_POST
foreach ($_POST as $key => $value) {
    //$_POST[$key] = removeWhiteSpace($value);
}

// Apply positive input validation to all elements in $_GET
foreach ($_GET as $key => $value) {
    $_GET[$key] = removeWhiteSpace($value);
}

// Check and sanitize all elements in $_POST
foreach ($_POST as $key => $value) {
    //$_POST[$key] = escapeHTML($value);
}

// Check and sanitize all elements in $_GET
foreach ($_GET as $key => $value) {
    //$_GET[$key] = escapeHTML($value);
}

// Apply positive input validation to all elements in $_POST
foreach ($_POST as $key => $value) {
    $_POST[$key] = whitelistInput($value);
}

// Apply positive input validation to all elements in $_GET
foreach ($_GET as $key => $value) {
    $_GET[$key] = whitelistInput($value);
}

// Apply HTML and URL decoding followed by validation to all elements in $_POST
foreach ($_POST as $key => $value) {
   $_POST[$key] = validateAndDecode($value);
}

// Apply HTML and URL decoding followed by validation to all elements in $_GET
foreach ($_GET as $key => $value) {
    $_GET[$key] = validateAndDecode($value);
}

?>
