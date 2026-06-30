<?php
    $mobile_ccode = $_GET['ccode'];
    $mobile_domain=$_GET['domain'];
    $mobile_token =$_GET['token'];
?>
<html>
    <head>
           <title> History </title>

<?php
  include("header.php");
?>

<style>
  .custom-swal-icon-size {
    font-size: 1rem !important;  /* Default is ~3rem */
  }

  /* Optional: target SVG if needed */
  .custom-swal-icon-size .swal2-icon {
    width: 1rem !important;
    height: 1rem !important;
  }
</style>

        <script>
           
               function getOrdershistory(){
                   $.ajax({
                       url:"getOrdershistory.php",
                       type:"POST",
                       data:"ccode="+<?php echo $mobile_ccode; ?>+"&domain="+"<?php echo $mobile_domain; ?>"+"&token="+"<?php echo $mobile_token; ?>",
                       success:function(data){
                           $("#tbl_container").html(data);
                       }
                   });
               }
    
           setInterval(function() {
             getOrdershistory();
           }, 5000);

        </script>
    </head>
    <body onload="getOrdershistory();">

        <div id="tbl_container"></div>
      <br><br><br><br>
    </body>
</html>