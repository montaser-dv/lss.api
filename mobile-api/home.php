<?php
/**
 * home_webview.php
 * Professional Data Bridge for Courier Dashboard Statistics
 * Communicates with React Native via window.ReactNativeWebView.postMessage
 */

header('Content-Type: text/html; charset=utf-8');

// 1. Retrieve Data via GET (Sent from Hidden WebView)
$mobile_ccode = $_GET['ccode'] ?? null;
$mobile_domain = $_GET['domain'] ?? null;
$mobile_token = $_GET['token'] ?? null;
$mobile_lat = $_GET['lat'] ?? '0';
$mobile_lng = $_GET['lng'] ?? '0';

// Initialize Response Array
$ls_array = array();
$error_code = null;

date_default_timezone_set('Asia/Riyadh');
$today = date("Y-m-d");

// 2. Security and Token Validation
if ($mobile_token && strlen($mobile_token) > 10) {

    if (!empty($mobile_ccode) && !empty($mobile_domain)) {

        include("config.php"); // Main database for domain lookup

        // Fetch Client's Specific Database Credentials
        $safe_domain = $mainDB->real_escape_string($mobile_domain);
        $safe_token = $mainDB->real_escape_string($mobile_token);

        $getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='$safe_domain' AND Token='$safe_token' LIMIT 1");

        if ($getData && $getData->num_rows > 0) {
            $row = $getData->fetch_array(MYSQLI_ASSOC);

            $DB_Name = $row['DB_Name'];
            $DB_User = $row['DB_User'];
            $DB_Pass = $row['DB_Pass'];

            // Connect to the specific client database
            $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);
            $db->set_charset("utf8");

            if (!$db->connect_error) {

                // Fetch Courier Profile & Balance
                $safe_ccode = $db->real_escape_string($mobile_ccode);
                $getCur = $db->query("SELECT * FROM couriers WHERE courier_code='$safe_ccode' LIMIT 1");

                if ($getCur && $getCur->num_rows > 0) {
                    $gc = $getCur->fetch_array(MYSQLI_ASSOC);

                    $info_array = [
                        'c_name' => $gc['Name'],
                        'c_cost' => $gc['Cost'],
                        'balance' => $gc['Balance'],
                        'today' => $today,
                        'id' => 0
                    ];

                    // Update Courier Real-time Location
                    $db->query("UPDATE couriers SET lat='$mobile_lat', lng='$mobile_lng' WHERE courier_code='$safe_ccode'");

                    // --- Today's Statistics ---

                    // Total Currency
                    $Curr = $db->query("SELECT *from companies where domain='$mobile_domain' ");
                    $ccurr = $Curr->fetch_array(MYSQLI_BOTH);
                    $info_array['Currency'] = $ccurr['currency'];


                    // Total Assigned Today
                    $total_today = $db->query("SELECT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='assign' AND created_at LIKE '%$today%'");
                    $info_array['total_today'] = $total_today->num_rows;

                    // total order today action
                    $total_today_action = $db->query("SELECT DISTINCT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status IN(7,13) AND created_at LIKE '%$today%'");
                    $info_array['total_today_action'] = $total_today_action->num_rows;


                    // total order today delevared
                    $total_today_delevared = $db->query("SELECT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status='7' AND created_at LIKE '%$today%' ");
                    $info_array['total_today_delevared'] = $total_today_delevared->num_rows;

                    // total order today not delevared
                $total_today_not_delevared = $db->query("SELECT DISTINCT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status='13' AND created_at LIKE '%$today%'");
                    $info_array['total_today_not_delevared'] = $total_today_not_delevared->num_rows;

                    // --- Lifetime Statistics ---
                    // All Orders Assigned from start
                    $total_order_from_start = $db->query("SELECT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='assign'");
                    $info_array['total_orders_from_start'] = $total_order_from_start->num_rows;

                    // All Orders actions from start
                    $total_orders_action_from_start = $db->query("SELECT DISTINCT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status IN(7,13)");
                    $info_array['total_orders_action_from_start'] = $total_orders_action_from_start->num_rows;

                    // All Orders delevared from start
                    $total_orders_delevared_from_start = $db->query("SELECT DISTINCT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status='7' ");
                    $info_array['total_orders_delevared_from_start'] = $total_orders_delevared_from_start->num_rows;

                    // All Orders not delevared from start
                    $total_orders_not_delevared_from_start = $db->query("SELECT DISTINCT AWB FROM order_paths WHERE courier_code='$safe_ccode' AND Action_type='status' AND status='13' ");
                    $info_array['total_orders_not_delevared_from_start'] = $total_orders_not_delevared_from_start->num_rows;
                    array_push($ls_array, $info_array);
                }
                $db->close();
            } else {
                $error_code = 7;
            } // DB Connection Error
        } else {
            $error_code = 2;
        } // Domain/Token Mismatch
        $mainDB->close();
    } else {
        $error_code = 4;
    } // Missing Parameters
} else {
    $error_code = 5;
} // Invalid Token

?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <script>
        /**
         * Post the resulting JSON to React Native
         * If an error occurred, we send the error code
         */
        const result = <?php echo !empty($ls_array) ? json_encode($ls_array) : json_encode(['error' => $error_code]); ?>;

        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify(result));
        }
    </script>
</body>

</html>