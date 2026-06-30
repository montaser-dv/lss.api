<?php

   $mobile_AWB =$_POST['awb'];
   $mobile_domain=$_POST['domain'];
   $mobile_token =$_POST['token'];

   //$arr=array();

   //array_push($arr,$mobile_domain);


  // echo json_encode($data);



   if(strlen($mobile_token) > 10){
   
   if(isset($mobile_AWB) && isset($mobile_domain) && isset($mobile_token)){
       
   include("config.php");
    //$mainDB = new mysqli('localhost', 'root', 'root1234', 'lss_main');
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

        $ConfirmOrder=$db->query("UPDATE orders SET courier_confirm='1' where AWB='$mobile_AWB' ");
          
        if($ConfirmOrder){echo 1;}else{echo 77;}

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