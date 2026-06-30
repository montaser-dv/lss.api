<?php

   //.........xx....yyy

   $secret = "105504801_170001144_147715555"; // <-- نفس السر في GitHub

      // استقبال توقيع GitHub
      $header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
      $payload = file_get_contents("php://input");

      // حساب التوقيع الصحيح
      $calculated = "sha256=" . hash_hmac("sha256", $payload, $secret);


      // تحقق من التوقيع
      if (!hash_equals($calculated, $header)) {

          file_put_contents("/home/trakmile.com/public_html/git_deploy/deploy_error.log",
              "Invalid Signature\n".
              "Received: $header\n".
              "Expected: $calculated\n\n",
              FILE_APPEND
          );

          //http_response_code(403);
          //exit("Invalid signature");
        $result="Invalid signature";
      }
      // نفّذ ملف التحديث

      $result = shell_exec(
        "/usr/bin/git -c safe.directory=/home/trakmile.com/public_html ".
        "-C /home/trakmile.com/public_html fetch origin 2>&1 && ".
        "/usr/bin/git -c safe.directory=/home/trakmile.com/public_html ".
        "-C /home/trakmile.com/public_html reset --hard origin/main 2>&1"
    );
      



    $resultx = shell_exec("
    whoami;
    pwd;
    ls -ld /home/trakmile.com/public_html/.git;
    /usr/bin/git -C /home/trakmile.com/public_html status;
    2>&1
    ");

    file_put_contents(
        __DIR__ . "/deploy.log",
        "\n====================\n".
        date('Y-m-d H:i:s')."\n".
        $result."\n".
        "====================\n",
        FILE_APPEND
    );

return $resultx;


?>
