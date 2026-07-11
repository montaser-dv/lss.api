<?php
include('lang.php');

$mobile_lang = mobile_get_lang();
header('Content-Type: application/json; charset=utf-8');

$mobile_ccode = trim((string) ($_POST['ccode'] ?? ''));
$mobile_domain = trim((string) ($_POST['domain'] ?? ''));
$mobile_token = trim((string) ($_POST['token'] ?? ''));
$page = max(1, (int) ($_POST['page'] ?? 1));
$limit = (int) ($_POST['limit'] ?? 20);
$limit = max(5, min(40, $limit));
$month = trim((string) ($_POST['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = '';
}

function history_json($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function history_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function history_month_label($ym, $lang)
{
    $ts = strtotime($ym . '-01');
    if ($ts === false) {
        return $ym;
    }

    if ($lang === 'ar') {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        $m = (int) date('n', $ts);
        return ($months[$m] ?? date('m', $ts)) . ' ' . date('Y', $ts);
    }

    return date('F Y', $ts);
}

if (strlen($mobile_token) <= 10) {
    history_json(['ok' => false, 'error' => 4], 400);
}

if ($mobile_ccode === '' || $mobile_domain === '' || $mobile_token === '') {
    history_json(['ok' => false, 'error' => 4], 400);
}

include('config.php');

$safeDomain = $mainDB->real_escape_string($mobile_domain);
$safeToken = $mainDB->real_escape_string($mobile_token);
$getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='$safeDomain' AND Token='$safeToken' LIMIT 1");

if (!$getData || $getData->num_rows === 0) {
    history_json(['ok' => false, 'error' => 9], 403);
}

$row = $getData->fetch_array(MYSQLI_ASSOC);
$db = new mysqli('localhost', $row['DB_User'], $row['DB_Pass'], $row['DB_Name']);
if ($db->connect_error) {
    history_json(['ok' => false, 'error' => 9], 500);
}
$db->set_charset('utf8mb4');

$safeCcode = $db->real_escape_string($mobile_ccode);
$dateExpr = "COALESCE(NULLIF(o.updated_at, '0000-00-00 00:00:00'), o.created_at)";
$baseWhere = "o.courier_code = '$safeCcode' AND o.archive = '1'";
$monthWhere = '';
if ($month !== '') {
    $safeMonth = $db->real_escape_string($month);
    $monthWhere = " AND DATE_FORMAT($dateExpr, '%Y-%m') = '$safeMonth'";
}

$months = [];
$monthsSql = "SELECT DATE_FORMAT($dateExpr, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM orders o
    WHERE $baseWhere
    GROUP BY ym
    ORDER BY ym DESC
    LIMIT 18";
$monthsRes = $db->query($monthsSql);
if ($monthsRes) {
    while ($m = $monthsRes->fetch_assoc()) {
        if (empty($m['ym'])) {
            continue;
        }
        $months[] = [
            'key' => $m['ym'],
            'label' => history_month_label($m['ym'], $mobile_lang),
            'count' => (int) $m['cnt'],
        ];
    }
}

$countRes = $db->query("SELECT COUNT(*) AS total FROM orders o WHERE $baseWhere $monthWhere");
$total = 0;
if ($countRes) {
    $total = (int) ($countRes->fetch_assoc()['total'] ?? 0);
}

$offset = ($page - 1) * $limit;
$getOrders = $db->query(
    "SELECT o.AWB, o.Reciver_phone, o.payment_method, o.created_at, o.updated_at,
            a.Name AS area_name, z.Name AS zone_name
     FROM orders o
     INNER JOIN zones z ON o.city = z.ID
     INNER JOIN areas a ON o.area = a.ID
     WHERE $baseWhere $monthWhere
     ORDER BY o.id DESC
     LIMIT $limit OFFSET $offset"
);

$html = '';
$rows = 0;

if ($getOrders && $getOrders->num_rows > 0) {
    while ($rc = $getOrders->fetch_assoc()) {
        $rows++;
        $awb = history_h($rc['AWB']);
        $city = history_h($rc['zone_name']);
        $area = history_h($rc['area_name']);
        $phone = history_h($rc['Reciver_phone']);
        $payment = history_h($rc['payment_method']);
        $whenRaw = $rc['updated_at'] && $rc['updated_at'] !== '0000-00-00 00:00:00'
            ? $rc['updated_at']
            : $rc['created_at'];
        $when = history_h($whenRaw);

        $html .= "
        <article class='history-card'>
            <div class='history-card-top'>
                <span class='history-awb'>{$awb}</span>
                <span class='history-city'>{$city}</span>
                <span class='history-area'>{$area}</span>
            </div>
            <div class='history-card-bottom'>
                <span class='history-phone'>{$phone}</span>
                <span class='history-meta'>
                    <span class='history-date'>{$when}</span>
                    <span class='history-payment'>{$payment}</span>
                </span>
                <span class='history-badge' aria-hidden='true'><i class='bi bi-archive'></i></span>
            </div>
        </article>";
    }
}

$hasMore = ($offset + $rows) < $total;

history_json([
    'ok' => true,
    'html' => $html,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'loaded' => $offset + $rows,
    'has_more' => $hasMore,
    'month' => $month,
    'months' => $months,
    'empty' => $total === 0,
    'empty_message' => mobile_t('empty_orders', $mobile_lang),
]);
