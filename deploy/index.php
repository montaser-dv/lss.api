<?php

//xxxx_hh_jj_22

   $secret = "105504801_170001144_147710923"; // <-- نفس السر في GitHub

      // استقبال توقيع GitHub
      $header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
      $payload = file_get_contents("php://input");

      // حساب التوقيع الصحيح
      $calculated = "sha256=" . hash_hmac("sha256", $payload, $secret);


      // تحقق من التوقيع
      if (!hash_equals($calculated, $header)) {

          file_put_contents("/home/demo.trakmile.com/public_html/deploy/deploy_error.log",
              "Invalid Signature\n".
              "Received: $header\n".
              "Expected: $calculated\n\n",
              FILE_APPEND
          );

          http_response_code(403);
          //exit("Invalid signature");
        $result="Invalid signature";
      }
      // نفّذ ملف التحديث

      $result = shell_exec("/bin/git -c safe.directory=/home/demo.trakmile.com/public_html -C /home/demo.trakmile.com/public_html pull origin main 2>&1");
      $result = shell_exec("/bin/git -c safe.directory=/home/demo.trakmile.com/public_html -C /home/demo.trakmile.com/public_html reset --hard origin/main 2>&1");
      

      //shell_exec("bash /home/demo.trakmile.com/public_html/deploy/deploy.sh 2>&1");
      //file_put_contents("/home/demo.trakmile.com/public_html/deploy/deploy.log", print_r($result, true));

      $resultx = shell_exec("
whoami
pwd
ls -la /home/demo.trakmile.com/public_html/.git
/bin/git --version
2>&1
");

file_put_contents(
    '/home/demo.trakmile.com/public_html/deploy/deploy.log',
    "\n====================\n" .
    date('Y-m-d H:i:s') . "\n" .
    $resultx .
    "\n====================\n",
    FILE_APPEND
);

return $resultx;


?>
