<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/platform_api.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$companyKey = isset($_GET['company']) ? trim((string) $_GET['company']) : '';
if ($id <= 0) {
    header('Location: tickets.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim((string) ($_POST['body'] ?? ''));
    $newStatus = trim((string) ($_POST['status'] ?? ''));

    if ($body === '') {
        $error = 'نص الرد مطلوب';
    } else {
        $payload = [
            'body' => $body,
            'author_name' => (string) ($_SESSION['admin_user'] ?? 'Trakmile Support'),
        ];
        if ($newStatus !== '') {
            $payload['status'] = $newStatus;
        }

        $result = platformRequest('POST', 'tickets/' . $id . '/reply', $payload);
        if ($result['ok']) {
            $redirect = 'ticket.php?id=' . $id . '&ok=1';
            if ($companyKey !== '') {
                $redirect .= '&company=' . urlencode($companyKey);
            }
            header('Location: ' . $redirect);
            exit;
        }
        $error = $result['error'] ?: ('فشل إرسال الرد' . ($result['status'] ? ' (' . $result['status'] . ')' : ''));
    }
}

$response = platformRequest('GET', 'tickets/' . $id);
$ticket = $response['ok'] ? ($response['data']['data'] ?? null) : null;

$statusLabels = [
    'new' => 'جديد',
    'open' => 'مفتوح',
    'in_progress' => 'قيد المعالجة',
    'waiting' => 'بانتظار',
    'resolved' => 'تم الحل',
    'closed' => 'مغلق',
];

$backHref = $companyKey !== ''
    ? 'tickets.php?company=' . urlencode($companyKey)
    : 'tickets.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= platformH($ticket['ticket_no'] ?? 'تذكرة') ?> | Trakmile Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; }
        .header {
            background: #0F172A; color: #fff; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .header-nav a {
            color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 600;
            padding: 8px 14px; border-radius: 8px; border: 1px solid transparent;
        }
        .header-nav a.active, .header-nav a:hover { color: #fff; background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .header-actions span { font-size: 14px; color: #94A3B8; }
        .header-actions a {
            color: #fff; text-decoration: none; font-size: 14px; padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.2); border-radius: 8px;
        }
        .container { max-width: 960px; margin: 0 auto; padding: 24px; }
        .card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 24px; margin-bottom: 16px;
        }
        .meta { font-size: 14px; color: #64748B; margin: 8px 0 16px; display: flex; flex-wrap: wrap; gap: 10px; }
        .desc { white-space: pre-wrap; line-height: 1.7; font-size: 14px; color: #334155; }
        .reply { border-top: 1px solid #E2E8F0; padding-top: 14px; margin-top: 14px; }
        .ok { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
        .err { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        textarea, select {
            width: 100%; padding: 12px 14px; border: 1px solid #E2E8F0; border-radius: 10px;
            font-family: inherit; font-size: 14px; margin-bottom: 12px;
        }
        button {
            background: #1B84FF; color: #fff; border: 0; border-radius: 10px; padding: 12px 18px;
            font-family: inherit; font-weight: 700; cursor: pointer;
        }
        button:hover { background: #156BCB; }
        .back { display: inline-block; margin-bottom: 16px; color: #1B84FF; text-decoration: none; font-weight: 600; }
        .status-pill {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            background: #EFF6FF; color: #1D4ED8; font-size: 12px; font-weight: 700;
        }
        <?= platformCompanyBannerCss() ?>
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1><?= platformH($ticket['ticket_no'] ?? 'تذكرة دعم') ?></h1>
            <div class="header-nav">
                <a href="index.php">طلبات الاستشارة</a>
                <a href="tickets.php" class="active">تذاكر الدعم</a>
            </div>
        </div>
        <div class="header-actions">
            <span><?= platformH($_SESSION['admin_user'] ?? '') ?></span>
            <a href="logout.php">تسجيل الخروج</a>
        </div>
    </div>

    <div class="container">
        <a class="back" href="<?= platformH($backHref) ?>">← رجوع لتذاكر الشركة</a>

        <?php if (isset($_GET['ok'])): ?>
            <div class="ok">تم إرسال الرد بنجاح.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="err"><?= platformH($error) ?></div>
        <?php endif; ?>
        <?php if (!$ticket): ?>
            <div class="err">تعذر تحميل التذكرة<?= $response['status'] ? ' (' . (int) $response['status'] . ')' : '' ?>: <?= platformH($response['error'] ?? '') ?></div>
        <?php else: ?>
            <?php platformRenderCompanyBanner(platformCompanyBannerData($ticket)); ?>

            <div class="card">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
                    <h2><?= platformH($ticket['subject'] ?? '') ?></h2>
                    <span class="status-pill"><?= platformH($statusLabels[$ticket['status'] ?? ''] ?? ($ticket['status'] ?? '')) ?></span>
                </div>
                <div class="meta">
                    <span><?= platformH($ticket['ticket_no'] ?? '') ?></span>
                    <span><?= platformH($ticket['creator']['name'] ?? '') ?></span>
                    <span><?= platformH($ticket['creator']['email'] ?? '') ?></span>
                </div>
                <div class="desc"><?= platformH($ticket['description'] ?? '') ?></div>
            </div>

            <div class="card">
                <h3 style="margin-bottom:8px;">المحادثة</h3>
                <?php if (empty($ticket['replies'])): ?>
                    <div class="meta">لا ردود بعد.</div>
                <?php endif; ?>
                <?php foreach (($ticket['replies'] ?? []) as $reply): ?>
                    <?php
                    $created = $reply['created_at'] ?? '';
                    if ($created !== '') {
                        $ts = strtotime($created);
                        $created = $ts ? date('Y-m-d H:i', $ts) : $created;
                    }
                    ?>
                    <div class="reply">
                        <div class="meta">
                            <strong><?= platformH($reply['user_name'] ?? '—') ?></strong>
                            <span><?= platformH($created) ?></span>
                        </div>
                        <div class="desc"><?= platformH($reply['body'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h3 style="margin-bottom:12px;">إضافة رد</h3>
                <form method="post">
                    <label for="body">الرد</label>
                    <textarea id="body" name="body" rows="5" required placeholder="اكتب ردك لشركة التوصيل..."></textarea>
                    <label for="status">تحديث الحالة (اختياري)</label>
                    <select id="status" name="status">
                        <option value="">الإبقاء على الحالة الحالية</option>
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?= platformH($key) ?>"><?= platformH($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">إرسال الرد</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
