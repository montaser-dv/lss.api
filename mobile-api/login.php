<?php
// منع المتصفح من كاش الصفحة لضمان تحديث البيانات
header('Cache-Control: no-cache, must-revalidate');

// استقبال البيانات عن طريق GET (العادي)
$mobile_email  = $_GET['mobile_email'] ?? '';
$mobile_pass   = $_GET['mobile_pass'] ?? '';
$mobile_domain = $_GET['mobile_domain'] ?? '';
$fcm_token     = $_GET['mobile_token'] ?? ''; // تم التعديل ليطابق الرابط المرسل

// دالة مساعدة لإرسال الرد إلى التطبيق عبر WebView
function sendResponseToApp($data) {
    $json = json_encode($data);
    echo "
    <script>
        window.ReactNativeWebView.postMessage('$json');
    </script>
    ";
    exit;
}

// 1. فحص بسيط قبل البدء
if (empty($mobile_email) || empty($mobile_pass) || empty($fcm_token)) {
    sendResponseToApp(4); // نقص في المعطيات
}

if (!empty($fcm_token)) {
    include("config.php");

    // 2. فحص النطاق (Sub Domain)
    $stmt_domain = $mainDB->prepare("SELECT * FROM domains WHERE Sub_Domain = ?");
    $stmt_domain->bind_param("s", $mobile_domain);
    $stmt_domain->execute();
    $getData = $stmt_domain->get_result();

    if ($getData->num_rows > 0) {
        $row = $getData->fetch_assoc();
        
        $DB_Name = $row['DB_Name'];
        $DB_User = $row['DB_User'];
        $DB_Pass = $row['DB_Pass'];

        // 3. الاتصال بقاعدة بيانات العميل
        $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);
        if ($db->connect_error) {
            sendResponseToApp(500); 
        }

         $md5_pass=md5($mobile_pass);
        // 4. البحث عن المندوب (Courier)
        $stmt_cur = $db->prepare("SELECT * FROM couriers WHERE email = ? AND password = ?");
        $stmt_cur->bind_param("ss", $mobile_email, $md5_pass);
        $stmt_cur->execute();
        $getCurier = $stmt_cur->get_result();

        if ($getCurier->num_rows > 0) {
            $rc = $getCurier->fetch_assoc();
            
            // تجهيز بيانات الرد (Object)
            $cur = [
                "ID"           => $rc['ID'],
                "Name"         => $rc['Name'],
                "email"        => $rc['email'],
                "phone"        => $rc['phone'],
                "License"      => $rc['License'],
                "Vehicle_Num"  => $rc['Vehicle_Num'],
                "Cost"         => $rc['Cost'],
                "Balance"      => $rc['Balance'],
                "courier_code" => $rc['courier_code'],
                "status"       => $rc['status'],
                "c_code"       => $rc['c_code'],
                "created_at"   => $rc['created_at']
            ];

            $courier_code = $rc['courier_code'];

            // 5. تحديث التوكن في قاعدة البيانات
            $stmt_upd = $db->prepare("UPDATE couriers SET fcm_token = ? WHERE courier_code = ?");
            $stmt_upd->bind_param("ss", $fcm_token, $courier_code);
            $update = $stmt_upd->execute();

            if ($update) {
                sendResponseToApp($cur); // نجاح، أرسل بيانات المستخدم
            } else {
                sendResponseToApp(2); // خطأ في التحديث
            }
        } else {
            sendResponseToApp(9); // إيميل أو باسوورد خطأ
        }
    } else {
        sendResponseToApp(9); // الدومين غير صحيح
    }
} else {
    sendResponseToApp(4); // التوكن مفقود
}
?>