<!DOCTYPE html>
<html lang="en" dir="ltr"> <head>
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
      if ($get_message->num_rows > 0) {
        $message_text = $get_message->fetch_array(MYSQLI_ASSOC)['message_text'];
        $new_message = str_replace("[AWB]", $mobile_AWB, $message_text);
        $safe_msg = htmlspecialchars(json_encode($new_message, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
      }

      $getOrders = $db->query("SELECT o.*,a.Name AS area_name,z.Name AS zone_name ,u.name AS client_name FROM orders o INNER JOIN zones z,areas a,users u where o.city=z.ID and o.area=a.ID and o.courier_code='$mobile_ccode' and o.Brand=u.id and o.AWB='$mobile_AWB' and o.archive='0' ");


      if ($getOrders->num_rows > 0) {

        $order_arr = array();
        //$ir=0;

        while ($rc = $getOrders->fetch_array(MYSQLI_BOTH)) {


          $cur['id'] = $rc['id'];
          $cur['AWB'] = $rc['AWB'];
          $cur['Brand'] = $rc['client_name'];
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

          $barcode_url = "https://" . $subdomain . "." . $domain . "/assets/order_barcode/" . $cur['AWB'] . ".png";

          $tbl .= "
               <tr>
               <td class='cap_oo'> Barcode </td><td style='color:#1b84ff' id='awb'>";
          $tbl .= "<img src='" . $barcode_url . "' width='170' height='50'/>";
          $tbl .= "</td>
               </tr>

               <tr class='hr-btm'>
               <td class='cap_oo'> AWB </td><td style='color:#1b84ff' id='awb'>" . $cur['AWB'] . "</td>
               </tr>

               <tr>
               <td class='cap_oo'>Reciver name</td><td>" . $cur['Reciver_name'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>Reciver phone</td><td>" . $cur['Reciver_phone'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>City</td><td>" . $cur['city'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>Area</td><td>" . $cur['area'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>Address</td><td>" . $cur['Address'] . "</td>
               </tr>
               <tr>
               <td class='cap_oo'>Payment method</td><td>" . $cur['payment_method'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'> COD </td><td>" . $cur['COD'] . "</td>
               </tr>



               <tr class='hr-btm'>
               <td class='cap_oo'>Pieces</td><td>" . $cur['Pieces'] . "</td>
               </tr>


               <tr>
               <td class='cap_oo'> Client name </td><td>" . $cur['Brand'] . "</td>
               </tr>

               <tr>
               <td class='cap_oo'>Description</td><td>" . $cur['description'] . "</td>
               </tr>
               <tr class='hr-btm'>
               <td class='cap_oo'>Notes</td><td>" . $cur['notes'] . "</td>
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
                  Open Location
                <i class='bi bi-pin-map-fill'></i>
              </button>
          ";
        }


        $tbl .= "<br><br>

                <button class='btn btn-primary btn-lk' onclick=delivared('delvery','" . $mobile_AWB . "','" . $mobile_domain . "','" . $mobile_token . "','" . $mobile_ccode . "')>
                    Delivered &nbsp; &nbsp; &nbsp;
                  <i class='bi bi-bag-check'></i>
                </button>

          <br><br>

           <button class='btn btn-danger btn-lk' onclick=not_delivared('not','" . $mobile_AWB . "','" . $mobile_domain . "','" . $mobile_token . "','" . $mobile_ccode . "')>
             Not delivered
             <i class='bi bi-bag-x'></i>
           </button>
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