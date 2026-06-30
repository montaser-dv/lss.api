<?php

require_once __DIR__ . '/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($id > 0 && in_array($action, ['read', 'contacted', 'new', 'delete'], true)) {
        if ($action === 'delete') {
            $stmt = $db->prepare('DELETE FROM quote_requests WHERE id = ?');
            $stmt->bind_param('i', $id);
        } else {
            $stmt = $db->prepare('UPDATE quote_requests SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $action, $id);
        }
        $stmt->execute();
        $stmt->close();
    }
    header('Location: index.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'new', 'read', 'contacted'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}

if ($filter === 'all') {
    $result = $db->query('SELECT * FROM quote_requests ORDER BY created_at DESC');
} else {
    $stmt = $db->prepare('SELECT * FROM quote_requests WHERE status = ? ORDER BY created_at DESC');
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $result = $stmt->get_result();
}

$quotes = $result->fetch_all(MYSQLI_ASSOC);

$counts = ['new' => 0, 'read' => 0, 'contacted' => 0, 'all' => 0];
$countResult = $db->query('SELECT status, COUNT(*) as cnt FROM quote_requests GROUP BY status');
while ($row = $countResult->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['cnt'];
    $counts['all'] += (int)$row['cnt'];
}

$db->close();

$statusLabels = [
    'new' => 'جديد',
    'read' => 'مقروء',
    'contacted' => 'تم التواصل',
];

$statusColors = [
    'new' => '#1B84FF',
    'read' => '#64748B',
    'contacted' => '#16A34A',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات العروض | Trakmile Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; }
        .header {
            background: #0F172A;
            color: #fff;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .header-actions span { font-size: 14px; color: #94A3B8; }
        .header-actions a {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
        }
        .header-actions a:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .filters a {
            padding: 8px 18px;
            border-radius: 100px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            background: #fff;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }
        .filters a.active { background: #1B84FF; color: #fff; border-color: #1B84FF; }
        .filters a .count {
            display: inline-block;
            background: rgba(0,0,0,0.08);
            padding: 1px 8px;
            border-radius: 100px;
            font-size: 12px;
            margin-right: 4px;
        }
        .filters a.active .count { background: rgba(255,255,255,0.25); }
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #64748B;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
        }
        .quote-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
        }
        .quote-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .quote-header h3 { font-size: 17px; font-weight: 700; }
        .quote-meta { font-size: 14px; color: #64748B; margin-top: 4px; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
        }
        .quote-desc {
            background: #F8FAFC;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #334155;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .quote-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .quote-actions form { display: inline; }
        .quote-actions button {
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            background: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: #334155;
        }
        .quote-actions button:hover { background: #F1F5F9; }
        .quote-actions button.danger { color: #DC2626; border-color: #FECACA; }
        .quote-actions button.danger:hover { background: #FEF2F2; }
        .quote-date { font-size: 12px; color: #94A3B8; margin-top: 12px; }
        @media (max-width: 600px) {
            .quote-header { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>طلبات العروض — Trakmile</h1>
        <div class="header-actions">
            <span><?= htmlspecialchars($_SESSION['admin_user']) ?></span>
            <a href="logout.php">تسجيل الخروج</a>
        </div>
    </div>

    <div class="container">
        <div class="filters">
            <a href="?status=all" class="<?= $filter === 'all' ? 'active' : '' ?>">
                <span class="count"><?= $counts['all'] ?></span> الكل
            </a>
            <a href="?status=new" class="<?= $filter === 'new' ? 'active' : '' ?>">
                <span class="count"><?= $counts['new'] ?></span> جديد
            </a>
            <a href="?status=read" class="<?= $filter === 'read' ? 'active' : '' ?>">
                <span class="count"><?= $counts['read'] ?></span> مقروء
            </a>
            <a href="?status=contacted" class="<?= $filter === 'contacted' ? 'active' : '' ?>">
                <span class="count"><?= $counts['contacted'] ?></span> تم التواصل
            </a>
        </div>

        <?php if (empty($quotes)): ?>
            <div class="empty">لا توجد طلبات عروض حالياً</div>
        <?php else: ?>
            <?php foreach ($quotes as $q): ?>
                <div class="quote-card">
                    <div class="quote-header">
                        <div>
                            <h3><?= htmlspecialchars($q['name']) ?></h3>
                            <div class="quote-meta">
                                <a href="tel:<?= htmlspecialchars($q['phone']) ?>"><?= htmlspecialchars($q['phone']) ?></a>
                                ·
                                <a href="mailto:<?= htmlspecialchars($q['email']) ?>"><?= htmlspecialchars($q['email']) ?></a>
                            </div>
                        </div>
                        <span class="status-badge" style="background:<?= $statusColors[$q['status']] ?>">
                            <?= $statusLabels[$q['status']] ?>
                        </span>
                    </div>
                    <?php if ($q['description']): ?>
                        <div class="quote-desc"><?= nl2br(htmlspecialchars($q['description'])) ?></div>
                    <?php endif; ?>
                    <div class="quote-actions">
                        <?php if ($q['status'] !== 'read'): ?>
                            <form method="post"><input type="hidden" name="id" value="<?= $q['id'] ?>"><input type="hidden" name="action" value="read"><button type="submit">تحديد كمقروء</button></form>
                        <?php endif; ?>
                        <?php if ($q['status'] !== 'contacted'): ?>
                            <form method="post"><input type="hidden" name="id" value="<?= $q['id'] ?>"><input type="hidden" name="action" value="contacted"><button type="submit">تم التواصل</button></form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('هل أنت متأكد من الحذف؟')"><input type="hidden" name="id" value="<?= $q['id'] ?>"><input type="hidden" name="action" value="delete"><button type="submit" class="danger">حذف</button></form>
                    </div>
                    <div class="quote-date"><?= date('Y-m-d H:i', strtotime($q['created_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
