<!DOCTYPE html>
<?php
include("lang.php");
include("order_helpers.php");
$mobile_lang = mobile_get_lang();
?>
<html lang="<?php echo htmlspecialchars($mobile_lang); ?>" dir="<?php echo mobile_dir($mobile_lang); ?>"> <head>
    <meta charset="UTF-8">
<?php

include("header.php");

//error_reporting(0);      // Turn off all error reporting
//ini_set('display_errors', 0);
// Retrieve the HTTP request method


$mobile_AWB = $_GET['awb'];//"A10111";
$mobile_ccode = $_GET['ccode'];
$mobile_domain = $_GET['domain'];
$mobile_token = $_GET['token'];

//echo $mobile_AWB.'-'.$mobile_ccode.'-'.$mobile_domain.'-'.$mobile_token;



//$arr=array();

global $tbl;


//echo json_encode($arr); 
$tbl .= "<table class='orderDetail'>";

if (strlen($mobile_token) > 10) {

  if (isset($mobile_ccode) && isset($mobile_domain) && isset($mobile_token)) {

    include("config.php");

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


      $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);


      $get_message = $db->query("SELECT message_text FROM whatsapp_message where status='Active' and message_type='Assign' ");
      $safe_msg = 'null';
      if ($get_message && $get_message->num_rows > 0) {
        $message_text = $get_message->fetch_array(MYSQLI_ASSOC)['message_text'];
        $new_message = str_replace("[AWB]", $mobile_AWB, $message_text);
        $safe_msg = htmlspecialchars(json_encode($new_message, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
      }

      $clientSelect = mobile_orders_client_select_sql();
      $clientJoin = mobile_orders_client_join_sql();
      $getOrders = $db->query("SELECT o.*,a.Name AS area_name,z.Name AS zone_name,u.name AS client_name,o.Brand AS order_brand,$clientSelect FROM orders o INNER JOIN zones z ON o.city=z.ID INNER JOIN areas a ON o.area=a.ID INNER JOIN users u ON o.Brand=u.id $clientJoin WHERE o.courier_code='$mobile_ccode' AND o.AWB='$mobile_AWB' AND o.archive='0' ");


      if ($getOrders->num_rows > 0) {

        $order_arr = array();
        //$ir=0;

        while ($rc = $getOrders->fetch_assoc()) {


          $cur['id'] = $rc['id'];
          $cur['AWB'] = $rc['AWB'];
          $cur['Brand'] = !empty($rc['client_business_name']) ? $rc['client_business_name'] : $rc['client_name'];
          $cur['Reciver_name'] = $rc['Reciver_name'];
          $cur['Reciver_phone'] = $rc['Reciver_phone'];
          $cur['COD'] = $rc['COD'];
          $cur['city'] = $rc['zone_name'];
          $cur['area'] = $rc['area_name'];
          $cur['payment_method'] = $rc['payment_method'];
          $cur['Pieces'] = $rc['Pieces'];
          $cur['Address'] = $rc['Address'];
          $cur['courier_confirm'] = $rc['courier_confirm'];
          $cur['lat'] = $rc['lat'];
          $cur['lng'] = $rc['lng'];
          $cur['notes'] = $rc['notes'];
          $cur['description'] = $rc['description'];
          $cur['created_at'] = $rc['created_at'];
          $cur['updated_at'] = $rc['updated_at'];

          $rc['Brand'] = $rc['order_brand'] ?? $rc['Brand'] ?? null;
          $order_type = mobile_get_order_type_from_row($rc, $db);
          $status_name = mobile_get_status_name_from_row($db, $rc);
          $show_picked_action = mobile_should_show_picked_action($order_type, $status_name);

          $barcode_url = "https://" . $subdomain . "." . $domain . "/assets/order_barcode/" . $cur['AWB'] . ".png";

          $tbl .= "
               <tr>
               <td class='cap_oo'>" . mobile_t('order_type', $mobile_lang) . "</td>
               <td><span class='order-badge order-badge-type'>" . htmlspecialchars(mobile_order_type_label($order_type, $mobile_lang)) . "</span></td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('order_status', $mobile_lang) . "</td>
               <td><span class='order-badge order-badge-status'>" . htmlspecialchars(mobile_status_label($status_name, $mobile_lang)) . "</span></td>
               </tr>
               <tr>
               <td class='cap_oo'> " . mobile_t('barcode', $mobile_lang) . " </td><td style='color:#1b84ff' id='awb'>";
          $tbl .= "<img src='" . $barcode_url . "' width='170' height='50'/>";
          $tbl .= "</td>
               </tr>

               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('awb', $mobile_lang) . "</td><td style='color:#1b84ff' id='awb'>" . $cur['AWB'] . "</td>
               </tr>

               <tr>
               <td class='cap_oo'>" . mobile_t('receiver_name', $mobile_lang) . "</td><td>" . $cur['Reciver_name'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>" . mobile_t('receiver_phone', $mobile_lang) . "</td><td>" . $cur['Reciver_phone'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>" . mobile_t('city', $mobile_lang) . "</td><td>" . $cur['city'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>" . mobile_t('area', $mobile_lang) . "</td><td>" . $cur['area'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('address', $mobile_lang) . "</td><td>" . $cur['Address'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>" . mobile_t('payment_method', $mobile_lang) . "</td><td>" . $cur['payment_method'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('cod', $mobile_lang) . "</td><td>" . $cur['COD'] . "</td>
               </tr>



               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('pieces', $mobile_lang) . "</td><td>" . $cur['Pieces'] . "</td>
               </tr>


               <tr>
               <td class='cap_oo'>" . mobile_t('client_name', $mobile_lang) . "</td><td>" . $cur['Brand'] . "</td>
               </tr>

               <tr>
               <td class='cap_oo'>" . mobile_t('description', $mobile_lang) . "</td><td>" . $cur['description'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>" . mobile_t('notes', $mobile_lang) . "</td><td>" . $cur['notes'] . "</td>
               </tr>
               ";

          //$ir++;
        }

        $tbl .= "</table><center>";


        $tbl .= "<br> <table width='200px' border='0'> <tr>";

        $tbl .= "<td style='border:0px solid #333'>
            <button class='btn btn-secondary btn-lk lo_style' onclick='shareNo(\"c\", \"" . $cur['Reciver_phone'] . "\", \"null\")'>
                <img src='imgs/call.png' style='position:relative;top:-2px' width='20'/>
            </button> 
         </td>";

$tbl .= "<td align='right'>
            <button class='btn btn-success btn-lk lo_style' onclick='shareNo(\"w\", \"" . $cur['Reciver_phone'] . "\", " . $safe_msg . ")'>
                <img src='imgs/whatsapp.png' style='position:relative;top:-2px' width='20'/>
            </button>
         </td>";

        $tbl .= "<tr></table>";




        if (!empty($cur['lat']) && !empty($cur['lng']) && $cur['lat'] !== 0 && $cur['lng'] !== 0) {
          $tbl .= "
              <button class='btn btn-warning btn-lk' onclick='openLocation(" . $cur['lat'] . "," . $cur['lng'] . ")'>
                  " . mobile_t('open_location', $mobile_lang) . "
                <i class='bi bi-pin-map-fill'></i>
              </button>
          ";
        }

        $tbl .= "<br><br>";

        if ($show_picked_action) {
          $tbl .= "
                <button class='btn btn-info btn-lk' onclick=pickedOrder('" . $mobile_AWB . "','" . $mobile_domain . "','" . $mobile_token . "','" . $mobile_ccode . "')>
                    " . mobile_t('picked', $mobile_lang) . " &nbsp; &nbsp; &nbsp;
                  <i class='bi bi-box-seam'></i>
                </button>
          ";
        } else {
          $tbl .= "
                <button class='btn btn-primary btn-lk' onclick=delivared('delvery','" . $mobile_AWB . "','" . $mobile_domain . "','" . $mobile_token . "','" . $mobile_ccode . "')>
                    " . mobile_t('delivered', $mobile_lang) . " &nbsp; &nbsp; &nbsp;
                  <i class='bi bi-bag-check'></i>
                </button>

          <br><br>

           <button class='btn btn-danger btn-lk' onclick=not_delivared('not','" . $mobile_AWB . "','" . $mobile_domain . "','" . $mobile_token . "','" . $mobile_ccode . "')>
             " . mobile_t('not_delivered', $mobile_lang) . "
             <i class='bi bi-bag-x'></i>
           </button>
          ";
        }

        $tbl .= "
       </center>
   <br><br><br>
           ";

        echo $tbl;

      }


    } else {
      echo 9;
    }

  } else {
    echo 4;
  }
}



?>