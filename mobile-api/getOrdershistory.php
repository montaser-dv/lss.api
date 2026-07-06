<?php
include("lang.php");
$mobile_lang = mobile_get_lang();
error_reporting(E_ALL);
ini_set('display_errors', 1);
//header('Content-Type: application/json');

// Retrieve the HTTP request method
    //$rawData = file_get_contents("php://input");
    //$data = json_decode($rawData, true);

   //$mobile_AWB =$data['AWB'];
   $mobile_ccode =$_POST['ccode'];
   $mobile_domain=$_POST['domain'];
   $mobile_token =$_POST['token'];
   //$arr=array();

   //array_push($arr,$mobile_domain);

   //echo json_encode($arr);

   if(strlen($mobile_token) > 10){

   if(isset($mobile_ccode) && isset($mobile_domain) && isset($mobile_token)){

    include("config.php");
    //$mainDB = new mysqli('localhost', 'lss4c_main', 'z!wde@rHjHvKHo#@', 'lss4c_lss');
    $getData=$mainDB->query("SELECT * FROM domains where Sub_Domain='$mobile_domain' and Token='$mobile_token' ");

    if($getData->num_rows > 0){

        $row=$getData->fetch_array(MYSQLI_ASSOC);
        $domain = $row['Domain'];
        $subdomain = $row['Sub_Domain'];
        $data['Token'] = $row['Token'];
        $expire_date = $row['expire_date'];
        $DB_Name = $row['DB_Name'];
        $DB_User = $row['DB_User'];
        $DB_Pass = $row['DB_Pass'];


        $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);

          global $tbl;

        $tbl.="<table class='min-tbl-item' style='color:#828282'>";


        $getOrders=$db->query("SELECT o.*,a.Name AS area_name,z.Name AS zone_name,u.name AS client_name FROM orders o INNER JOIN zones 
        z,areas a,users u where o.city=z.ID and o.area=a.ID and o.courier_code='$mobile_ccode'and o.Brand=u.id and o.archive='1' order by id desc");


         if($getOrders->num_rows > 0){

             $order_arr=array();
             //$ir=0;

            while($rc=$getOrders->fetch_array(MYSQLI_BOTH)){


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


                $box_class = "tbl-item-confirm";
                $btn_content="<img src='imgs/archive.png' width='20px'>";
                $color="color:#828282";
            



            $tbl.="<tr><td>";

             if($cur['courier_confirm'] == 1){
                $tbl.=" <a style='text-decoration: none;' href='javascript:void(0)'>";
             }else{
                $tbl.="<a style='text-decoration: none;' href='#'>";
             }


            $tbl.="<table class='".$box_class."' style='background:#F5F5F5' border='0'>
                     <tr>
            <td id='awb' style='".$color."'>".$cur['AWB']."</td>
            <td>".$cur['city']."</td>
            <td style='text-align: center;'>".$cur['area']."</td>
                   </tr>

                  <tr>
            <td>".$cur['Reciver_phone']."</td>

            <td>".$cur['created_at']."&nbsp <font color='#313131'>".$cur['payment_method']."</font> </td>
            <td style='text-align: right;'>
                 $btn_content
            </td>
                 </tr>
             </table>
               </a>
                </td>
            </tr>
            ";



            }


        $tbl.="</table>";



          
   }else{
       $tbl.="<tr><td align='center'> <br> " . mobile_t('empty_orders', $mobile_lang) . " <br><br> </td></tr>";
       
   }
   
   echo $tbl;


   }
   else{
    echo 9;
   }

}
else{
    echo 4;
}
}

?>