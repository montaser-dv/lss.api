<?php
/**
 * 1. Base Header Configuration
 * Note: Since we are rendering HTML for WebView, Content-Type is set to text/html.
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: text/html; charset=utf-8');
header("Connection: close");

/**
 * 2. Start Output Buffering
 */
ob_start();

/**
 * Retrieve data from URL parameters (GET)
 */
$mobile_AWB    = $_GET['barcode'] ?? null;
$mobile_domain = $_GET['domain'] ?? null;
$mobile_token  = $_GET['token'] ?? null;

$status = "error"; // Default status
$response_message = ""; 

// Validate essential data
if (!empty($mobile_AWB) && !empty($mobile_domain)) {
        
    include("config.php"); 
    
    if (isset($mainDB)) {
        $safe_domain = $mainDB->real_escape_string($mobile_domain);
        $getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='$safe_domain' LIMIT 1");

        if ($getData && $getData->num_rows > 0) {
            $row = $getData->fetch_array(MYSQLI_ASSOC);
            
            $DB_Name = $row['DB_Name'];
            $DB_User = $row['DB_User'];
            $DB_Pass = $row['DB_Pass'];

            $db = new mysqli('127.0.0.1', $DB_User, $DB_Pass, $DB_Name);

            if ($db->connect_error) {
                $response_message = "Database Connection Error"; 
            } else {
                $safe_awb = $db->real_escape_string($mobile_AWB);
                $ConfirmOrder = $db->query("UPDATE orders SET courier_confirm='1' WHERE AWB='$safe_awb'");

                if ($ConfirmOrder) {
                    $status = "success";
                    $response_message = "Order Confirmed Successfully!"; 
                } else {
                    $response_message = "Update Failed in Client DB"; 
                }
                $db->close();
            }
        } else {
            $response_message = "Invalid Domain"; 
        }
        $mainDB->close();
    } else {
        $response_message = "Configuration Missing"; 
    }
} else {
    $response_message = "Missing Parameters"; 
}

/**
 * 3. Construct HTML Response for WebView
 * This sends a message to React Native using window.ReactNativeWebView.postMessage
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f8f9fa; }
        .container { text-align: center; padding: 20px; }
        .icon { fontSize: 50px; margin-bottom: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><?php echo ($status === 'success') ? '✅' : '⚠️'; ?></div>
        <h2 class="<?php echo $status; ?>"><?php echo ($status === 'success') ? 'Success' : 'Failed'; ?></h2>
        <p><?php echo $response_message; ?></p>
    </div>

    <script>
        /**
         * Sending the server response to React Native
         */
        (function() {
            var responseData = {
                status: "<?php echo $status; ?>",
                message: "<?php echo $response_message; ?>",
                barcode: "<?php echo $mobile_AWB; ?>"
            };

            // Check if WebView bridge is available
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify(responseData));
            }
        })();
    </script>
</body>
</html>

<?php
/**
 * 4. Finalizing the response for fast delivery
 */
$size = ob_get_length();
header("Content-Length: $size");

ob_end_flush();
@ob_flush();
flush();

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
exit();
?>