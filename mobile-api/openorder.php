<!DOCTYPE html>
<?php
include('lang.php');
include('order_helpers.php');

$mobile_lang = mobile_get_lang();
$mobile_dir = mobile_dir($mobile_lang);

function mobile_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mobile_order_field($label, $value, $options = [])
{
    $value = trim((string) $value);
    if ($value === '' && empty($options['allow_empty'])) {
        return '';
    }
    if ($value === '') {
        $value = '—';
    }

    $valueClass = !empty($options['highlight']) ? ' order-field-value--highlight' : '';
    $fullWidth = !empty($options['full']) ? ' order-field--full' : '';

    return '
        <div class="order-field' . $fullWidth . '">
            <span class="order-field-label">' . mobile_h($label) . '</span>
            <span class="order-field-value' . $valueClass . '">' . mobile_h($value) . '</span>
        </div>';
}

function mobile_status_badge_class($statusName)
{
    $status = mobile_normalize_status_name($statusName);

    if (in_array($status, ['delivered', 'completed', 'closed'], true)) {
        return 'order-badge-status--success';
    }
    if (in_array($status, ['not_delivered', 'cancelled', 'canceled', 'returned'], true)) {
        return 'order-badge-status--danger';
    }
    if ($status === 'picked') {
        return 'order-badge-status--info';
    }

    return 'order-badge-status--warning';
}

function mobile_type_badge_class($orderType)
{
    return mobile_normalize_order_type($orderType) === 'fulfillment'
        ? 'order-badge-type--fulfillment'
        : 'order-badge-type--lastmile';
}
?>
<html lang="<?php echo mobile_h($mobile_lang); ?>" dir="<?php echo mobile_h($mobile_dir); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo mobile_h(mobile_t('page_title.order_detail', $mobile_lang)); ?></title>
<?php include('header.php'); ?>
</head>
<body class="order-page">

<?php
$mobile_AWB = $_GET['awb'] ?? '';
$mobile_ccode = $_GET['ccode'] ?? '';
$mobile_domain = $_GET['domain'] ?? '';
$mobile_token = $_GET['token'] ?? '';

if (strlen($mobile_token) <= 10 || !isset($mobile_ccode, $mobile_domain, $mobile_token)) {
    echo 4;
    echo '</body></html>';
    exit;
}

include('config.php');

$getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='$mobile_domain' AND Token='$mobile_token'");

if (!$getData || $getData->num_rows === 0) {
    echo 9;
    echo '</body></html>';
    exit;
}

$row = $getData->fetch_array(MYSQLI_ASSOC);
$domain = $row['Domain'];
$subdomain = $row['Sub_Domain'];
$DB_Name = $row['DB_Name'];
$DB_User = $row['DB_User'];
$DB_Pass = $row['DB_Pass'];

$db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);

$safe_msg = 'null';
$get_message = $db->query("SELECT message_text FROM whatsapp_message WHERE status='Active' AND message_type='Assign'");
if ($get_message && $get_message->num_rows > 0) {
    $message_text = $get_message->fetch_array(MYSQLI_ASSOC)['message_text'];
    $new_message = str_replace('[AWB]', $mobile_AWB, $message_text);
    $safe_msg = htmlspecialchars(json_encode($new_message, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

$clientSelect = mobile_orders_client_select_sql();
$usersJoin = mobile_orders_users_join_sql();
$clientJoin = mobile_orders_client_join_sql();
$getOrders = $db->query(
    "SELECT o.*, a.Name AS area_name, z.Name AS zone_name, u.name AS client_user_name, o.Brand AS order_brand, $clientSelect
     FROM orders o
     INNER JOIN zones z ON o.city = z.ID
     INNER JOIN areas a ON o.area = a.ID
     $usersJoin
     $clientJoin
     WHERE o.courier_code = '$mobile_ccode' AND o.AWB = '$mobile_AWB' AND o.archive = '0'"
);

if (!$getOrders || $getOrders->num_rows === 0) {
    echo '</body></html>';
    exit;
}

$rc = $getOrders->fetch_assoc();

$cur = [
    'AWB' => $rc['AWB'],
    'Brand' => !empty($rc['client_business_name']) ? $rc['client_business_name'] : ($rc['client_user_name'] ?? ''),
    'Reciver_name' => $rc['Reciver_name'],
    'Reciver_phone' => $rc['Reciver_phone'],
    'COD' => $rc['COD'],
    'city' => $rc['zone_name'],
    'area' => $rc['area_name'],
    'payment_method' => $rc['payment_method'],
    'Pieces' => $rc['Pieces'],
    'Address' => $rc['Address'],
    'lat' => $rc['lat'],
    'lng' => $rc['lng'],
    'notes' => $rc['notes'],
    'description' => $rc['description'],
];

$rc['Brand'] = $rc['order_brand'] ?? $rc['Brand'] ?? null;
$order_type = mobile_get_order_type_from_row($rc, $db);
$status_name = mobile_get_status_name_from_row($db, $rc);
$show_picked_action = mobile_should_show_picked_action($order_type, $status_name);
$has_location = !empty($cur['lat']) && !empty($cur['lng']) && $cur['lat'] != 0 && $cur['lng'] != 0;
$barcode_url = 'https://' . $subdomain . '.' . $domain . '/assets/order_barcode/' . $cur['AWB'] . '.png';
$safe_phone = mobile_h($cur['Reciver_phone']);
$safe_awb = mobile_h($mobile_AWB);
$safe_domain = mobile_h($mobile_domain);
$safe_token = mobile_h($mobile_token);
$safe_ccode = mobile_h($mobile_ccode);
$type_label = mobile_order_type_label($order_type, $mobile_lang);
$status_label = mobile_status_label($status_name, $mobile_lang);
$type_normalized = mobile_normalize_order_type($order_type);
$type_desc_key = $type_normalized === 'fulfillment'
    ? 'order_type_fulfillment_desc'
    : ($type_normalized === 'last_mile' ? 'order_type_last_mile_desc' : '');
$type_icon = $type_normalized === 'fulfillment' ? 'bi-building' : 'bi-truck';
?>

<div class="order-shell">
    <header class="order-hero">
        <div class="order-hero-top">
            <span class="order-hero-label"><?php echo mobile_h(mobile_t('awb', $mobile_lang)); ?></span>
            <span class="order-hero-awb"><?php echo mobile_h($cur['AWB']); ?></span>
        </div>

        <div class="order-barcode-wrap">
            <img src="<?php echo mobile_h($barcode_url); ?>" alt="<?php echo mobile_h(mobile_t('barcode', $mobile_lang)); ?>" class="order-barcode-img" width="200" height="56">
        </div>
    </header>

    <main class="order-content">
        <section class="order-card order-card--status">
            <h2 class="order-card-title">
                <i class="bi bi-info-circle"></i>
                <?php echo mobile_h(mobile_t('section_status_type', $mobile_lang)); ?>
            </h2>
            <div class="order-status-panel">
                <div class="order-status-item">
                    <div class="order-status-item-head">
                        <span class="order-status-icon order-status-icon--type">
                            <i class="bi <?php echo mobile_h($type_icon); ?>"></i>
                        </span>
                        <span class="order-status-item-label"><?php echo mobile_h(mobile_t('order_type', $mobile_lang)); ?></span>
                    </div>
                    <span class="order-badge order-badge-type order-badge--panel <?php echo mobile_type_badge_class($order_type); ?>">
                        <?php echo mobile_h($type_label); ?>
                    </span>
                    <?php if ($type_desc_key !== ''): ?>
                    <p class="order-status-item-desc"><?php echo mobile_h(mobile_t($type_desc_key, $mobile_lang)); ?></p>
                    <?php endif; ?>
                </div>

                <div class="order-status-divider" aria-hidden="true"></div>

                <div class="order-status-item">
                    <div class="order-status-item-head">
                        <span class="order-status-icon order-status-icon--status">
                            <i class="bi bi-flag-fill"></i>
                        </span>
                        <span class="order-status-item-label"><?php echo mobile_h(mobile_t('order_status', $mobile_lang)); ?></span>
                    </div>
                    <span class="order-badge order-badge-status order-badge--panel <?php echo mobile_status_badge_class($status_name); ?>">
                        <?php echo mobile_h($status_label); ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="order-card">
            <h2 class="order-card-title">
                <i class="bi bi-person-circle"></i>
                <?php echo mobile_h(mobile_t('section_receiver', $mobile_lang)); ?>
            </h2>
            <div class="order-card-body">
                <?php
                echo mobile_order_field(mobile_t('receiver_name', $mobile_lang), $cur['Reciver_name'], ['highlight' => true]);
                echo mobile_order_field(mobile_t('receiver_phone', $mobile_lang), $cur['Reciver_phone'], ['highlight' => true]);
                ?>
            </div>
        </section>

        <section class="order-card">
            <h2 class="order-card-title">
                <i class="bi bi-geo-alt"></i>
                <?php echo mobile_h(mobile_t('section_location', $mobile_lang)); ?>
            </h2>
            <div class="order-card-body">
                <?php
                echo mobile_order_field(mobile_t('city', $mobile_lang), $cur['city']);
                echo mobile_order_field(mobile_t('area', $mobile_lang), $cur['area']);
                echo mobile_order_field(mobile_t('address', $mobile_lang), $cur['Address'], ['full' => true]);
                ?>
            </div>
        </section>

        <section class="order-card">
            <h2 class="order-card-title">
                <i class="bi bi-credit-card"></i>
                <?php echo mobile_h(mobile_t('section_payment', $mobile_lang)); ?>
            </h2>
            <div class="order-card-body order-card-body--grid">
                <?php
                echo mobile_order_field(mobile_t('payment_method', $mobile_lang), $cur['payment_method']);
                echo mobile_order_field(mobile_t('cod', $mobile_lang), $cur['COD'], ['highlight' => true]);
                echo mobile_order_field(mobile_t('pieces', $mobile_lang), $cur['Pieces']);
                ?>
            </div>
        </section>

        <section class="order-card">
            <h2 class="order-card-title">
                <i class="bi bi-box-seam"></i>
                <?php echo mobile_h(mobile_t('section_order_info', $mobile_lang)); ?>
            </h2>
            <div class="order-card-body">
                <?php
                echo mobile_order_field(mobile_t('client_name', $mobile_lang), $cur['Brand'], ['highlight' => true]);
                echo mobile_order_field(mobile_t('description', $mobile_lang), $cur['description'], ['full' => true, 'allow_empty' => true]);
                echo mobile_order_field(mobile_t('notes', $mobile_lang), $cur['notes'], ['full' => true, 'allow_empty' => true]);
                ?>
            </div>
        </section>
    </main>

    <div class="order-toolbar">
        <div class="order-contact-row">
            <button type="button" class="order-contact-btn order-contact-btn--call" onclick='shareNo("c", "<?php echo $safe_phone; ?>", "null")' aria-label="<?php echo mobile_h(mobile_t('call', $mobile_lang)); ?>">
                <img src="imgs/call.png" alt="" width="22" height="22">
                <span><?php echo mobile_h(mobile_t('call', $mobile_lang)); ?></span>
            </button>
            <button type="button" class="order-contact-btn order-contact-btn--whatsapp" onclick='shareNo("w", "<?php echo $safe_phone; ?>", <?php echo $safe_msg; ?>)' aria-label="<?php echo mobile_h(mobile_t('whatsapp', $mobile_lang)); ?>">
                <img src="imgs/whatsapp.png" alt="" width="22" height="22">
                <span><?php echo mobile_h(mobile_t('whatsapp', $mobile_lang)); ?></span>
            </button>
        </div>

        <?php if ($has_location): ?>
        <button type="button" class="order-action-btn order-action-btn--map" onclick="openLocation(<?php echo (float) $cur['lat']; ?>,<?php echo (float) $cur['lng']; ?>)">
            <i class="bi bi-pin-map-fill"></i>
            <?php echo mobile_h(mobile_t('open_location', $mobile_lang)); ?>
        </button>
        <?php endif; ?>

        <?php if ($show_picked_action): ?>
        <button type="button" class="order-action-btn order-action-btn--picked" onclick="pickedOrder('<?php echo $safe_awb; ?>','<?php echo $safe_domain; ?>','<?php echo $safe_token; ?>','<?php echo $safe_ccode; ?>')">
            <i class="bi bi-box-seam"></i>
            <?php echo mobile_h(mobile_t('picked', $mobile_lang)); ?>
        </button>
        <?php else: ?>
        <div class="order-delivery-row">
            <button type="button" class="order-action-btn order-action-btn--delivered" onclick="delivared('delvery','<?php echo $safe_awb; ?>','<?php echo $safe_domain; ?>','<?php echo $safe_token; ?>','<?php echo $safe_ccode; ?>')">
                <i class="bi bi-bag-check"></i>
                <?php echo mobile_h(mobile_t('delivered', $mobile_lang)); ?>
            </button>
            <button type="button" class="order-action-btn order-action-btn--failed" onclick="not_delivared('not','<?php echo $safe_awb; ?>','<?php echo $safe_domain; ?>','<?php echo $safe_token; ?>','<?php echo $safe_ccode; ?>')">
                <i class="bi bi-bag-x"></i>
                <?php echo mobile_h(mobile_t('not_delivered', $mobile_lang)); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
