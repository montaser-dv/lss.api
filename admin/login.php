<?php

require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$dbError = adminDbError();
$adminTableMissing = !$dbError && !adminTableExists('admin_users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $result = attemptLogin($username, $password);
        if (!empty($result['ok'])) {
            header('Location: index.php');
            exit;
        }
        if ($result['error'] === 'db_connect') {
            $error = 'فشل الاتصال بقاعدة البيانات: ' . htmlspecialchars($result['message']);
        } elseif ($result['error'] === 'no_table') {
            $error = 'جدول admin_users غير موجود. نفّذ quote_tables.sql على trak_db';
        } elseif ($result['error'] === 'db_query') {
            $error = 'خطأ في قاعدة البيانات: ' . htmlspecialchars($result['message']);
        } else {
            $error = 'بيانات الدخول غير صحيحة';
        }
    } else {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | Trakmile Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0F172A, #1E293B);
            padding: 20px;
        }
        .login-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        .login-box h1 { font-size: 22px; margin-bottom: 8px; color: #0F172A; }
        .login-box p { color: #64748B; font-size: 14px; margin-bottom: 28px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            margin-bottom: 18px;
        }
        input:focus { outline: none; border-color: #1B84FF; box-shadow: 0 0 0 3px rgba(27,132,255,0.15); }
        button {
            width: 100%;
            padding: 13px;
            background: #1B84FF;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: #156BCB; }
        button:disabled { background: #94A3B8; cursor: not-allowed; }
        .error, .warn {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 18px;
            line-height: 1.6;
        }
        .error { background: #FEF2F2; color: #DC2626; }
        .warn { background: #FFFBEB; color: #B45309; }
        .hint { font-size: 12px; color: #94A3B8; margin-top: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>لوحة إدارة Trakmile</h1>
        <p>سجّل الدخول لاستعراض طلبات الاستشارة</p>

        <?php if ($dbError): ?>
            <div class="error">فشل الاتصال بقاعدة البيانات:<br><?= htmlspecialchars($dbError) ?></div>
        <?php elseif ($adminTableMissing): ?>
            <div class="warn">جدول admin_users غير موجود. نفّذ ملف docs/DB/quote_tables.sql على قاعدة trak_db</div>
        <?php endif; ?>

        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

        <form method="post">
            <label for="username">اسم المستخدم</label>
            <input type="text" id="username" name="username" required autocomplete="username" value="admin">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit" <?= ($dbError || $adminTableMissing) ? 'disabled' : '' ?>>دخول</button>
        </form>
        <p class="hint">الافتراضي: admin / Trakmile@2026<br><a href="https://trakmile.com" style="color:#1B84FF">← العودة للموقع</a></p>
    </div>
</body>
</html>
