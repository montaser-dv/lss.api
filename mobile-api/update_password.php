<?php
include("config.php");

// استقبال البيانات
$courier_code = isset($_GET['code']) ? $_GET['code'] : '';
$new_password = isset($_GET['pass']) ? $_GET['pass'] : '';
$domain = isset($_GET['mobile_domain']) ? $_GET['mobile_domain'] : ''; // استقبال الدومين

$response = array("status" => "error", "message" => "An error occurred");

if ($courier_code != "" && $new_password != "") {
    
    $md5_password = md5($new_password);
  
      $sub_domain = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain = '$domain' ");
      if ($sub_domain->num_rows > 0) {
        $row = $sub_domain->fetch_assoc();
        
        $DB_Name = $row['DB_Name'];
        $DB_User = $row['DB_User'];
        $DB_Pass = $row['DB_Pass'];

        // 3. الاتصال بقاعدة بيانات العميل
        $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);
      }


    // تحديث الاستعلام ليشمل الدومين إذا كنت تحتاجه للتحقق
    // مثال: UPDATE couriers SET password = '$md5_password' WHERE courier_code = '$courier_code' AND subdomain = '$domain'
    $sql = "UPDATE couriers SET password = '$md5_password' WHERE courier_code = '$courier_code'";
    
    $result = $db->query($sql);

    if ($result) {
        if ($db->affected_rows > 0) {
            $response["status"] = "success";
            $response["message"] = "Password updated successfully!";
        } else {
            $response["message"] = "No changes made. Check code or domain.";
        }
    } else {
        $response["message"] = "Database Error: " . $db->error;
    }

} else {
    $response["message"] = "Missing parameters.";
}

$json_response = json_encode($response);

echo "
<html>
<body>
    <script>
        window.onload = function() {
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage('$json_response');
            }
        };
    </script>
</body>
</html>
";

$db->close();
?>