<?php
    include("lang.php");
    $mobile_lang = mobile_get_lang();
    $mobile_ccode = $_GET['ccode'];
    $mobile_domain=$_GET['domain'];
    $mobile_token =$_GET['token'];
?>
<html lang="<?php echo htmlspecialchars($mobile_lang); ?>" dir="<?php echo mobile_dir($mobile_lang); ?>">
    <head>
           <title><?php echo htmlspecialchars(mobile_t('page_title.orders', $mobile_lang)); ?></title>

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
           
               function getOrders(){
                   $.ajax({
                       url:"getOrders.php",
                       type:"POST",
                       data:"ccode="+<?php echo $mobile_ccode; ?>+"&domain="+"<?php echo $mobile_domain; ?>"+"&token="+"<?php echo $mobile_token; ?>"+"&lang="+"<?php echo $mobile_lang; ?>",
                       success:function(data){
                           $("#tbl_container").html(data);
                       }
                   });
               }
    
           setInterval(function() {
             getOrders();
           }, 5000);

        </script>
    </head>
    <body onload="getOrders();">

        <div id="tbl_container"></div>
      <br><br><br><br>
    </body>
</html>