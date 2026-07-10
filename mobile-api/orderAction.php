<?php

include("order_helpers.php");

$mobile_AWB = $_POST['awb'];
$mobile_ccode = $_POST['ccode'];
$mobile_domain = $_POST['domain'];
$mobile_token = $_POST['token'];


$mobile_comment = $_POST['comment'];
$mobile_type = $_POST['otype'];

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
            $clientJoin = mobile_orders_client_join_sql();
            $getOrders = $db->query("SELECT o.*, o.Brand AS order_brand, $clientSelect FROM orders o LEFT JOIN users u ON o.Brand=u.id $clientJoin WHERE o.AWB='$mobile_AWB' ");


            if ($getOrders->num_rows > 0) {

                //$ir=0;

                $rc = $getOrders->fetch_assoc();


                $idw = $rc['id'];
                $payment_method = $rc['payment_method'];
                $COD = $rc['COD'];
                $rc['Brand'] = $rc['order_brand'] ?? $rc['Brand'] ?? null;
                $order_type = mobile_get_order_type_from_row($rc, $db);
                $current_status = mobile_get_status_name_from_row($db, $rc);

                if ($mobile_type == 'picked') {
                    if (!mobile_should_show_picked_action($order_type, $current_status)) {
                        echo 8;
                        exit;
                    }

                    $status_id = mobile_find_status_id($db, 'picked');
                    if (!$status_id) {
                        echo 6;
                        exit;
                    }

                    $upp = $db->query("UPDATE orders SET Status='$status_id',archive='0' WHERE AWB='$mobile_AWB' ");
                    $insert = $db->query("INSERT INTO order_paths value('0','$mobile_AWB','status','$status_id','$mobile_ccode','0','$mobile_comment','$today','$today','$c_code')");

                    echo ($upp && $insert) ? 1 : 2;
                    exit;
                }

                if ($mobile_type == 'delvery') {
                    $ttype = 'Delivered';
                    $status_id = 7;
                } else {
                    $ttype = 'Not delivered';
                    $status_id = 13;
                }


                global $upp;


                $upp = $db->query("UPDATE orders SET Status='$status_id',archive='1' WHERE AWB='$mobile_AWB' ");


                //$courier_id = substr($mobile_ccode, 2, 20);


                $insert = $db->query("INSERT INTO order_paths value('0','$mobile_AWB','status','$status_id','$mobile_ccode','0','$mobile_comment','$today','$today','$c_code')");

                if ($mobile_type == 'delvery' && $status_id == 7 && $payment_method == 'Cash') {
                    $current_b = $db->query("SELECT Balance FROM couriers WHERE courier_code='$mobile_ccode'");
                    $cb = $current_b->fetch_array(MYSQLI_BOTH);
                    $new_balance = $cb['Balance'] + $COD;
                    $update = $db->query("UPDATE couriers SET Balance='$new_balance' WHERE courier_code='$mobile_ccode'");
                }

                if ($upp) {
                    echo 1;
                } else {
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