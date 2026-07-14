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
    "SELECT o.*, a.Name AS area_name, z.Name AS zone_name, u.name AS client_user_name, o.Status AS order_status_id, o.Brand AS order_brand, $clientSelect
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
$action_context = mobile_resolve_order_action_context($db, $rc);
$order_type = $action_context['order_type'];
$status_info = $action_context['status'];
$status_short_name = $status_info['short_name'];
$status_id = $status_info['id'];
$show_picked_action = $action_context['show_picked'];
$is_picked_status = mobile_is_picked_status($status_short_name, $status_id);
$can_courier_act = mobile_can_courier_act_on_order($status_short_name, $status_id);
$show_delivery_actions = $can_courier_act && !$show_picked_action;
$has_location = mobile_has_valid_coords($cur['lat'] ?? null, $cur['lng'] ?? null);
$client_lat = $rc['client_lat'] ?? null;
$client_lng = $rc['client_lng'] ?? null;
$has_client_location = mobile_has_valid_coords($client_lat, $client_lng);
$show_order_location_button = $has_location && !$show_picked_action && !$is_picked_status;
$barcode_url = 'https://' . $subdomain . '.' . $domain . '/assets/order_barcode/' . $cur['AWB'] . '.png';
$safe_phone = mobile_h($cur['Reciver_phone']);
$safe_awb = mobile_h($mobile_AWB);
$safe_domain = mobile_h($mobile_domain);
$safe_token = mobile_h($mobile_token);
$safe_ccode = mobile_h($mobile_ccode);
$order_action_config = json_encode([
    'awb' => (string) $mobile_AWB,
    'domain' => (string) $mobile_domain,
    'token' => (string) $mobile_token,
    'ccode' => (string) $mobile_ccode,
    'payment_method' => (string) $cur['payment_method'],
    'otp_required' => mobile_otp_is_required($rc['otp_required'] ?? '') ? 'yes' : 'no',
    'lang' => $mobile_lang,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$picked_theme = $show_picked_action || $is_picked_status;
$is_fulfillment = mobile_normalize_order_type($order_type) === 'fulfillment';
$fulfillment_theme = $is_fulfillment && $show_delivery_actions;
$shell_theme_class = '';
if ($picked_theme) {
    $shell_theme_class = ' order-shell--picked';
} elseif ($fulfillment_theme) {
    $shell_theme_class = ' order-shell--fulfillment';
}
?>

<div class="order-shell<?php echo $shell_theme_class; ?>">
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
        <?php if ($show_picked_action && $has_client_location): ?>
        <button type="button" class="order-contact-btn order-location-toolbar-btn<?php echo $picked_theme ? ' order-btn--orange' : ''; ?>" onclick="openLocation(<?php echo (float) $client_lat; ?>,<?php echo (float) $client_lng; ?>)">
            <i class="bi bi-geo-alt-fill order-contact-icon" aria-hidden="true"></i>
            <span><?php echo mobile_h(mobile_t('open_client_location', $mobile_lang)); ?></span>
        </button>
        <?php endif; ?>

        <?php if ($show_order_location_button): ?>
        <button type="button" class="order-contact-btn order-location-toolbar-btn" onclick="openLocation(<?php echo (float) $cur['lat']; ?>,<?php echo (float) $cur['lng']; ?>)">
            <i class="bi bi-geo-alt-fill order-contact-icon" aria-hidden="true"></i>
            <span><?php echo mobile_h(mobile_t('open_client_location', $mobile_lang)); ?></span>
        </button>
        <?php endif; ?>

        <div class="order-contact-row">
            <button type="button" class="order-contact-btn order-contact-btn--call<?php echo $picked_theme ? ' order-btn--orange' : ''; ?>" onclick='shareNo("c", "<?php echo $safe_phone; ?>", "null")' aria-label="<?php echo mobile_h(mobile_t('call', $mobile_lang)); ?>">
                <i class="bi bi-telephone-fill order-contact-icon" aria-hidden="true"></i>
                <span><?php echo mobile_h(mobile_t('call', $mobile_lang)); ?></span>
            </button>
            <button type="button" class="order-contact-btn order-contact-btn--whatsapp<?php echo $picked_theme ? ' order-btn--orange' : ''; ?>" onclick='shareNo("w", "<?php echo $safe_phone; ?>", <?php echo $safe_msg; ?>)' aria-label="<?php echo mobile_h(mobile_t('whatsapp', $mobile_lang)); ?>">
                <i class="bi bi-whatsapp order-contact-icon" aria-hidden="true"></i>
                <span><?php echo mobile_h(mobile_t('whatsapp', $mobile_lang)); ?></span>
            </button>
        </div>

        <?php if ($is_picked_status): ?>
        <div class="order-picked-notice">
            <i class="bi bi-check2-circle"></i>
            <span><?php echo mobile_h(mobile_t('picked_waiting_warehouse', $mobile_lang)); ?></span>
        </div>
        <?php elseif ($show_picked_action): ?>
        <button type="button" class="order-action-btn order-action-btn--picked" onclick="openOrderActionModal('picked')">
            <i class="bi bi-box-seam"></i>
            <?php echo mobile_h(mobile_t('picked', $mobile_lang)); ?>
        </button>
        <?php elseif ($show_delivery_actions): ?>
        <div class="order-delivery-row">
            <button type="button" class="order-action-btn order-action-btn--delivered" onclick="openOrderActionModal('delvery')">
                <i class="bi bi-bag-check"></i>
                <?php echo mobile_h(mobile_t('delivered', $mobile_lang)); ?>
            </button>
            <button type="button" class="order-action-btn order-action-btn--failed" onclick="openOrderActionModal('not')">
                <i class="bi bi-bag-x"></i>
                <?php echo mobile_h(mobile_t('not_delivered', $mobile_lang)); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.orderActionConfig = <?php echo $order_action_config; ?>;
</script>

</body>
</html>
