<?php
/**
 * WebView-compatible API for Domain Verification
 * This script communicates with React Native via window.ReactNativeWebView.postMessage
 */

header('Content-Type: text/html; charset=utf-8');

// 1. Capture Data from GET (since it's coming from WebView URL)
$mobile_domain = $_GET['mobile_domain'] ?? '';
$mobile_token  = "FxLo5ptqfuXanaCc65ycYNrK_cNGnyYDhasG0jhgGcTtbNs7uO-ViOSbn3DnOy05BsldRfbu3j_ZHZJwR23BU3g";

$response = [
    'status' => 'error',
    'message' => 'Initial state',
    'Sub_Domain' => '',
    'expire_date' => '',
    'Domain' => ''
];

// 2. Logic Validation
if (strlen($mobile_domain) > 1) {
    
    include("config.php");

    if ($mainDB->connect_error) {
        $response['message'] = "Database Connection Error";
        $result_code = 7; // Matching your previous logic
    } else {
        $mainDB->set_charset("utf8");
        
        // Sanitize Input
        $safe_domain = $mainDB->real_escape_string($mobile_domain);
        
        $getQuery = "SELECT * FROM domains WHERE Sub_Domain='$safe_domain' AND Token='$mobile_token' LIMIT 1";
        $getData = $mainDB->query($getQuery);

        if ($getData && $getData->num_rows > 0) {
            $row = $getData->fetch_assoc();
            
            $response['status']      = 'success';
            $response['Domain']      = $row['Domain'];
            $response['Sub_Domain']  = $row['Sub_Domain'];
            $response['Token']       = $row['Token'];
            $response['expire_date'] = $row['expire_date'];
            $response['message']     = "Domain verified successfully";
        } else {
            $response['message'] = "This domain is not registered!";
            $result_code = 2;
        }
    }
} else {
    $response['message'] = "Subdomain name must be entered!";
    $result_code = 8;
}

/**
 * 3. Output HTML with JavaScript Bridge
 * The JavaScript below sends the $response object back to React Native
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            background: #F8F9FA; 
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007AFF;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader"></div>

    <script>
        // Data prepared by PHP
        const responseData = <?php echo json_encode($response); ?>;

        // Bridge: Send data to React Native
        window.onload = function() {
            if (window.ReactNativeWebView) {
                // We send the JSON string to the onMessage handler in React Native
                window.ReactNativeWebView.postMessage(JSON.stringify(responseData));
            }
        };
    </script>
</body>
</html>