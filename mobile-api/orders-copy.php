<?php

header('Content-Type: application/json');

// Retrieve the HTTP request method
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

   //$mobile_AWB =$data['AWB'];
   $mobile_ccode =$data['ccode'];
   $mobile_domain=$data['domain'];
   $mobile_token ="FxLo5ptqfuXanaCc65ycYNrK_cNGnyYDhasG0jhgGcTtbNs7uO-ViOSbn3DnOy05BsldRfbu3j_ZHZJwR23BU3g";//$data['token'];
   
   
   //$arr=array();
   
   //array_push($arr,$mobile_domain);

   
   //echo json_encode($arr); 


   if(strlen($mobile_token) > 10){

   if(isset($mobile_ccode) && isset($mobile_domain) && isset($mobile_token)){

    $mainDB = new mysqli('localhost', 'lss_main_user', '*f^yaId^^k7awNHF', 'lss_main');
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


        $getOrders=$db->query("SELECT o.*,a.Name AS area_name,z.Name AS zone_name,u.name AS client_name FROM orders o INNER JOIN zones z,areas a,users u where o.city=z.ID and o.area=a.ID and o.courier_code='$mobile_ccode'and o.Brand=u.id and o.archive='0' ");


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
            
            array_push($order_arr,$cur); 
            
            //$ir++;
            }

     
        $arr=json_encode($order_arr);
        
        $output = trim($arr, '[]');
        
        echo $arr;

   }


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