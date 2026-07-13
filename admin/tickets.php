<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/platform_api.php';
requireAdmin();

$status = isset($_GET['status']) ? (string) $_GET['status'] : 'open';
$allowed = ['all', 'new', 'open', 'resolved', 'closed'];
if (!in_array($status, $allowed, true)) {
    $status = 'open';
}

$companyKey = isset($_GET['company']) ? trim((string) $_GET['company']) : '';

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

/**
 * @param array $ticket
 * @return array{key:string,label:string,url:?string,c_code:?string}
 */
function ticketCompanyMeta(array $ticket): array
{
    $sub = trim((string) ($ticket['origin_subdomain'] ?? $ticket['origin_label'] ?? ''));
    $host = trim((string) ($ticket['origin_host'] ?? ''));
    $url = trim((string) ($ticket['origin_url'] ?? ''));
    $cCode = trim((string) ($ticket['c_code'] ?? ''));

    if ($sub !== '') {
        $key = 'sub:' . strtolower($sub);
        $label = $sub;
    } elseif ($host !== '') {
        $key = 'host:' . strtolower($host);
        $label = $host;
    } elseif ($cCode !== '') {
        $key = 'code:' . $cCode;
        $label = 'شركة ' . $cCode;
    } else {
        $key = 'unknown';
        $label = 'غير معروف';
    }

    return [
        'key' => $key,
        'label' => $label,
        'url' => $url !== '' ? $url : ($host !== '' ? 'https://' . $host : null),
        'c_code' => $cCode !== '' ? $cCode : null,
    ];
}

$folders = [];
foreach ($tickets as $ticket) {
    $meta = ticketCompanyMeta($ticket);
    $key = $meta['key'];
    if (!isset($folders[$key])) {
        $folders[$key] = [
            'key' => $key,
            'label' => $meta['label'],
            'url' => $meta['url'],
            'c_code' => $meta['c_code'],
            'company' => is_array($ticket['company'] ?? null) ? $ticket['company'] : null,
            'count' => 0,
            'new' => 0,
            'open' => 0,
            'tickets' => [],
        ];
    }
    if (empty($folders[$key]['company']) && is_array($ticket['company'] ?? null)) {
        $folders[$key]['company'] = $ticket['company'];
    }
    if (!empty($ticket['company']['name'])) {
        $folders[$key]['label'] = (string) $ticket['company']['name'];
    }
    $folders[$key]['count']++;
    $st = (string) ($ticket['status'] ?? '');
    if ($st === 'new') {
        $folders[$key]['new']++;
    }
    if (!in_array($st, ['resolved', 'closed'], true)) {
        $folders[$key]['open']++;
    }
    $folders[$key]['tickets'][] = $ticket;
}

uasort($folders, static function ($a, $b) {
    return strcasecmp($a['label'], $b['label']);
});

$activeFolder = null;
$folderTickets = [];
if ($companyKey !== '') {
    foreach ($folders as $folder) {
        if ($folder['key'] === $companyKey || $folder['label'] === $companyKey) {
            $activeFolder = $folder;
            $folderTickets = $folder['tickets'];
            break;
        }
    }
}

$viewMode = $activeFolder ? 'tickets' : 'folders';
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
        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }
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
        .folders {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        @media (max-width: 700px) {
            .folders { grid-template-columns: 1fr; }
        }
        .folder {
            display: block;
            text-decoration: none;
            color: inherit;
            background: #fff;
            border: 2px solid #0F172A;
            border-radius: 4px;
            min-height: 140px;
            padding: 22px 20px;
            transition: background .15s ease, box-shadow .15s ease;
        }
        .folder:hover {
            background: #F8FAFC;
            box-shadow: 0 8px 20px rgba(15,23,42,.08);
        }
        .folder-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .folder-meta { color: #64748B; font-size: 13px; line-height: 1.7; }
        .folder-counts {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #EFF6FF;
            color: #1D4ED8;
        }
        .pill.warn { background: #FEF3C7; color: #B45309; }
        .back {
            display: inline-block;
            margin-bottom: 16px;
            color: #1B84FF;
            text-decoration: none;
            font-weight: 700;
        }
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
        .quote-actions a {
            display: inline-block; padding: 7px 14px; border-radius: 8px;
            background: #1B84FF; color: #fff; text-decoration: none; font-size: 13px; font-weight: 600;
        }
        .quote-date { font-size: 12px; color: #94A3B8; margin-top: 12px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .section-sub { color: #64748B; font-size: 14px; margin-bottom: 20px; }
        <?= platformCompanyBannerCss() ?>
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>تذاكر الدعم التقني — Trakmile</h1>
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
                $href = '?status=' . urlencode($key);
                if ($viewMode === 'tickets' && $activeFolder) {
                    $href .= '&company=' . urlencode($activeFolder['key']);
                }
            ?>
                <a href="<?= $href ?>" class="<?= $status === $key ? 'active' : '' ?>">
                    <span class="count"><?= (int) ($stats[$key] ?? 0) ?></span> <?= platformH($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($viewMode === 'folders'): ?>
            <div class="section-title">مجلدات الشركات</div>
            <div class="section-sub">كل شركة لها مجلد منفصل يحتوي تذاكرها</div>

            <?php if ($response['ok'] && empty($folders)): ?>
                <div class="empty">لا توجد تذاكر دعم في هذا التصفية</div>
            <?php else: ?>
                <div class="folders">
                    <?php foreach ($folders as $folder): ?>
                        <a class="folder" href="?status=<?= urlencode($status) ?>&company=<?= urlencode($folder['key']) ?>">
                            <div class="folder-title"><?= platformH($folder['label']) ?></div>
                            <div class="folder-meta">
                                <?php if (!empty($folder['url'])): ?>
                                    <?= platformH($folder['url']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($folder['c_code'])): ?>
                                    كود الشركة: <?= platformH($folder['c_code']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="folder-counts">
                                <span class="pill"><?= (int) $folder['count'] ?> تذكرة</span>
                                <?php if ((int) $folder['new'] > 0): ?>
                                    <span class="pill warn"><?= (int) $folder['new'] ?> جديدة</span>
                                <?php endif; ?>
                                <?php if ((int) $folder['open'] > 0): ?>
                                    <span class="pill"><?= (int) $folder['open'] ?> مفتوحة</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <a class="back" href="?status=<?= urlencode($status) ?>">← رجوع إلى مجلدات الشركات</a>
            <?php
            $bannerSource = $activeFolder;
            if (empty($bannerSource['company']) && !empty($folderTickets[0])) {
                $bannerSource = array_merge($bannerSource, $folderTickets[0]);
            }
            platformRenderCompanyBanner(platformCompanyBannerData($bannerSource, $activeFolder));
            ?>

            <?php if (empty($folderTickets)): ?>
                <div class="empty">لا توجد تذاكر لهذه الشركة في هذا التصفية</div>
            <?php endif; ?>

            <?php foreach ($folderTickets as $ticket): ?>
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
                            <h3><?= platformH($ticket['creator']['name'] ?? '—') ?></h3>
                            <div class="quote-meta">
                                <?= platformH($ticket['ticket_no'] ?? '') ?>
                                <?php if (!empty($ticket['creator']['email'])): ?>
                                    · <a href="mailto:<?= platformH($ticket['creator']['email']) ?>"><?= platformH($ticket['creator']['email']) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="status-badge" style="background:<?= $statusColors[$ticketStatus] ?? '#64748B' ?>">
                            <?= platformH($statusLabels[$ticketStatus] ?? $ticketStatus) ?>
                        </span>
                    </div>
                    <div class="quote-desc"><strong><?= platformH($ticket['subject'] ?? '') ?></strong></div>
                    <div class="quote-actions">
                        <a href="ticket.php?id=<?= (int) ($ticket['id'] ?? 0) ?>&company=<?= urlencode($activeFolder['key']) ?>">فتح التذكرة والرد</a>
                    </div>
                    <div class="quote-date"><?= platformH($created) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
