<?php

include("order_helpers.php");

$mobile_AWB = $_POST['awb'];
$mobile_ccode = $_POST['ccode'];
$mobile_domain = $_POST['domain'];
$mobile_token = $_POST['token'];


$mobile_comment = $_POST['comment'] ?? 0;
$mobile_type = $_POST['otype'];
$mobile_barcode = trim($_POST['barcode'] ?? '');
$mobile_pod = trim($_POST['pod_file'] ?? '');

//echo $mobile_AWB."-".$mobile_ccode."-".$mobile_domain."-".$mobile_token."-".$mobile_comment."-".$mobile_type;



//echo json_encode($mobile_type);


date_default_timezone_set('Asia/riyadh');
$today = date("Y-m-d H:i:s");

//$arr=array();

//array_push($arr,$mobile_domain);


//echo json_encode($arr); 




if (strlen($mobile_token) > 10) {

    if (isset($mobile_ccode) && isset($mobile_domain) && isset($mobile_token)) {

        include("config.php");
        //$mainDB = new mysqli('localhost', 'root', 'root1234', 'lss_main');
        $getData = $mainDB->query("SELECT * FROM domains where Sub_Domain='$mobile_domain' and Token='$mobile_token' ");

        if ($getData->num_rows > 0) {

            $row = $getData->fetch_array(MYSQLI_ASSOC);
            $domain = $row['Domain'];
            $subdomain = $row['Sub_Domain'];
            $data['Token'] = $row['Token'];
            $expire_date = $row['expire_date'];
            $DB_Name = $row['DB_Name'];
            $DB_User = $row['DB_User'];
            $DB_Pass = $row['DB_Pass'];
            $c_code = $row['c_code'];


            $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);


            $clientSelect = mobile_orders_client_select_sql();
            $usersJoin = mobile_orders_users_join_sql();
            $clientJoin = mobile_orders_client_join_sql();
            $getOrders = $db->query("SELECT o.*, o.Status AS order_status_id, o.Brand AS order_brand, $clientSelect FROM orders o $usersJoin $clientJoin WHERE o.courier_code='$mobile_ccode' AND o.AWB='$mobile_AWB' ");


            if ($getOrders->num_rows > 0) {

                //$ir=0;

                $rc = $getOrders->fetch_assoc();


                $idw = $rc['id'];
                $payment_method = $rc['payment_method'];
                $COD = $rc['COD'];

                if ($mobile_barcode === '') {
                    echo 10;
                    exit;
                }

                if (mobile_payment_requires_pod($payment_method) && $mobile_pod === '') {
                    echo 11;
                    exit;
                }

                if ($mobile_barcode !== (string) $mobile_AWB) {
                    echo 12;
                    exit;
                }

                $rc['Brand'] = $rc['order_brand'] ?? $rc['Brand'] ?? null;
                $action_context = mobile_resolve_order_action_context($db, $rc);
                $order_type = $action_context['order_type'];

                if ($mobile_type == 'picked') {
                    if (!$action_context['show_picked']) {
                        echo 8;
                        exit;
                    }

                    $status_id = mobile_find_status_id($db, 'picked') ?: 2;

                    $db->begin_transaction();
                    $upp = $db->query("UPDATE orders SET Status='$status_id', archive='0' WHERE AWB='$mobile_AWB' ");
                    $insert = mobile_insert_order_status_path($db, $mobile_AWB, $status_id, $mobile_ccode, $mobile_comment, $c_code, $mobile_pod);

                    if ($upp && $insert) {
                        $db->commit();
                        echo 1;
                    } else {
                        $db->rollback();
                        echo 2;
                    }
                    exit;
                }

                if ($mobile_type == 'delvery') {
                    $ttype = 'Delivered';
                    $status_id = 7;
                } else {
                    $ttype = 'Not delivered';
                    $status_id = 13;
                }


                if ($mobile_type == 'not' && (int) $mobile_comment <= 0) {
                    echo 14;
                    exit;
                }

                global $upp;

                $db->begin_transaction();
                $upp = $db->query("UPDATE orders SET Status='$status_id',archive='1' WHERE AWB='$mobile_AWB' ");
                $insert = mobile_insert_order_status_path($db, $mobile_AWB, $status_id, $mobile_ccode, $mobile_comment, $c_code, $mobile_pod);

                if ($mobile_type == 'delvery' && $status_id == 7 && $payment_method == 'Cash') {
                    $current_b = $db->query("SELECT Balance FROM couriers WHERE courier_code='$mobile_ccode'");
                    $cb = $current_b->fetch_array(MYSQLI_BOTH);
                    $new_balance = $cb['Balance'] + $COD;
                    $update = $db->query("UPDATE couriers SET Balance='$new_balance' WHERE courier_code='$mobile_ccode'");
                }

                if ($upp && $insert) {
                    $db->commit();
                    echo 1;
                } else {
                    $db->rollback();
                    echo 2;
                }



            } else {
                echo 7;
            }


        } else {
            echo 9;
        }

    } else {
        echo 4;
    }
}

?>