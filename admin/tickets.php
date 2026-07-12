<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/platform_api.php';
requireAdmin();

$status = isset($_GET['status']) ? (string) $_GET['status'] : 'open';
$allowed = ['all', 'new', 'open', 'resolved', 'closed'];
if (!in_array($status, $allowed, true)) {
    $status = 'open';
}

$response = platformRequest('GET', 'tickets?status=' . urlencode($status));
$tickets = $response['ok'] ? ($response['data']['data'] ?? []) : [];
$stats = $response['ok'] ? ($response['data']['stats'] ?? []) : [
    'all' => 0, 'new' => 0, 'open' => 0, 'resolved' => 0, 'closed' => 0, 'urgent' => 0,
];

$statusLabels = [
    'new' => 'جديد',
    'open' => 'مفتوح',
    'in_progress' => 'قيد المعالجة',
    'waiting' => 'بانتظار',
    'resolved' => 'تم الحل',
    'closed' => 'مغلق',
];

$statusColors = [
    'new' => '#1B84FF',
    'open' => '#0EA5E9',
    'in_progress' => '#F59E0B',
    'waiting' => '#64748B',
    'resolved' => '#16A34A',
    'closed' => '#94A3B8',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذاكر الدعم | Trakmile Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; }
        .header {
            background: #0F172A; color: #fff; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header-nav { display: flex; gap: 8px; flex-wrap: wrap; }
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
        .header-actions a:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .filters { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .filters a {
            padding: 8px 18px; border-radius: 100px; text-decoration: none; font-size: 14px; font-weight: 600;
            background: #fff; color: #64748B; border: 1px solid #E2E8F0;
        }
        .filters a.active { background: #1B84FF; color: #fff; border-color: #1B84FF; }
        .filters a .count {
            display: inline-block; background: rgba(0,0,0,0.08); padding: 1px 8px;
            border-radius: 100px; font-size: 12px; margin-right: 4px;
        }
        .filters a.active .count { background: rgba(255,255,255,0.25); }
        .empty, .error {
            text-align: center; padding: 40px 20px; border-radius: 12px; border: 1px solid #E2E8F0; margin-bottom: 16px;
        }
        .empty { color: #64748B; background: #fff; }
        .error { color: #DC2626; background: #FEF2F2; border-color: #FECACA; text-align: right; }
        .quote-card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 24px; margin-bottom: 16px;
        }
        .quote-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .quote-header h3 { font-size: 17px; font-weight: 700; }
        .quote-meta { font-size: 14px; color: #64748B; margin-top: 4px; }
        .status-badge {
            padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700; color: #fff; white-space: nowrap;
        }
        .quote-desc {
            background: #F8FAFC; padding: 14px 16px; border-radius: 8px; font-size: 14px;
            color: #334155; line-height: 1.7; margin-bottom: 16px;
        }
        .quote-actions a, .quote-actions button {
            display: inline-block; padding: 7px 14px; border-radius: 8px; border: 1px solid #E2E8F0;
            background: #1B84FF; color: #fff; text-decoration: none; font-family: inherit;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .quote-date { font-size: 12px; color: #94A3B8; margin-top: 12px; }
        .code-badge {
            display: inline-block; padding: 2px 8px; border-radius: 100px; font-size: 11px; font-weight: 700;
            background: #EFF6FF; color: #1B84FF; margin-left: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>تذاكر الدعم التقني — Trakmile</h1>
            <div class="header-nav" style="margin-top:8px;">
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
        <?php if (!$response['ok']): ?>
            <div class="error">
                تعذر جلب التذاكر<?= $response['status'] ? ' (' . (int) $response['status'] . ')' : '' ?>:
                <?= platformH($response['error'] ?? 'خطأ غير معروف') ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <?php
            $filters = [
                'all' => 'الكل',
                'new' => 'جديد',
                'open' => 'مفتوح',
                'resolved' => 'تم الحل',
                'closed' => 'مغلق',
            ];
            foreach ($filters as $key => $label):
            ?>
                <a href="?status=<?= urlencode($key) ?>" class="<?= $status === $key ? 'active' : '' ?>">
                    <span class="count"><?= (int) ($stats[$key] ?? 0) ?></span> <?= platformH($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($response['ok'] && empty($tickets)): ?>
            <div class="empty">لا توجد تذاكر دعم في هذا التصفية</div>
        <?php endif; ?>

        <?php foreach ($tickets as $ticket): ?>
            <?php
            $ticketStatus = (string) ($ticket['status'] ?? 'new');
            $created = $ticket['created_at'] ?? '';
            if ($created !== '') {
                $ts = strtotime($created);
                $created = $ts ? date('Y-m-d H:i', $ts) : $created;
            }
            ?>
            <div class="quote-card">
                <div class="quote-header">
                    <div>
                        <h3>
                            <?= platformH($ticket['creator']['name'] ?? '—') ?>
                            <?php if (!empty($ticket['c_code'])): ?>
                                <span class="code-badge"><?= platformH($ticket['c_code']) ?></span>
                            <?php endif; ?>
                        </h3>
                        <div class="quote-meta">
                            <?= platformH($ticket['ticket_no'] ?? '') ?>
                            <?php if (!empty($ticket['creator']['email'])): ?>
                                · <a href="mailto:<?= platformH($ticket['creator']['email']) ?>"><?= platformH($ticket['creator']['email']) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($ticket['module'])): ?>
                                · <?= platformH($ticket['module']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-badge" style="background:<?= $statusColors[$ticketStatus] ?? '#64748B' ?>">
                        <?= platformH($statusLabels[$ticketStatus] ?? $ticketStatus) ?>
                    </span>
                </div>
                <div class="quote-desc"><strong><?= platformH($ticket['subject'] ?? '') ?></strong></div>
                <div class="quote-actions">
                    <a href="ticket.php?id=<?= (int) ($ticket['id'] ?? 0) ?>">فتح التذكرة والرد</a>
                </div>
                <div class="quote-date"><?= platformH($created) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
