<?php
include("lang.php");
include("order_helpers.php");
$mobile_lang = mobile_get_lang();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mobile_ccode = $_POST['ccode'] ?? '';
$mobile_domain = $_POST['domain'] ?? '';
$mobile_token = $_POST['token'] ?? '';

if (strlen($mobile_token) > 10) {

    if (isset($mobile_ccode) && isset($mobile_domain) && isset($mobile_token)) {

        include("config.php");
        $getData = $mainDB->query("SELECT * FROM domains where Sub_Domain='$mobile_domain' and Token='$mobile_token' ");

        if ($getData->num_rows > 0) {

            $row = $getData->fetch_array(MYSQLI_ASSOC);
            $DB_Name = $row['DB_Name'];
            $DB_User = $row['DB_User'];
            $DB_Pass = $row['DB_Pass'];

            $db = new mysqli('localhost', $DB_User, $DB_Pass, $DB_Name);

            global $tbl;

            $tbl .= "<table class='min-tbl-item'>";

            $clientSelect = mobile_orders_client_select_sql();
            $usersJoin = mobile_orders_users_join_sql();
            $clientJoin = mobile_orders_client_join_sql();

            $getOrders = $db->query(
                "SELECT o.*, o.Status AS order_status_id, o.Brand AS order_brand, a.Name AS area_name, z.Name AS zone_name, u.name AS client_name, $clientSelect
                 FROM orders o
                 INNER JOIN zones z ON o.city = z.ID
                 INNER JOIN areas a ON o.area = a.ID
                 $usersJoin
                 $clientJoin
                 WHERE o.courier_code = '$mobile_ccode'
                   AND o.archive = '0'
                   AND o.Status <> '3'
                 ORDER BY id DESC"
            );

            if ($getOrders && $getOrders->num_rows > 0) {

                while ($rc = $getOrders->fetch_array(MYSQLI_BOTH)) {

                    $statusInfo = mobile_get_order_status_info($db, $rc);
                    $statusId = (int) $statusInfo['id'];
                    $statusShort = $statusInfo['short_name'];

                    if (mobile_is_warehouse_received_status($statusShort, $statusId)) {
                        continue;
                    }

                    $orderType = mobile_get_order_type_from_row($rc, $db);
                    $isPickup = mobile_normalize_order_type($orderType) === 'last_mile';

                    $cur = [
                        'id' => $rc['id'],
                        'AWB' => $rc['AWB'],
                        'Brand' => $rc['client_name'],
                        'Reciver_name' => $rc['Reciver_name'],
                        'Reciver_phone' => $rc['Reciver_phone'],
                        'COD' => $rc['COD'],
                        'city' => $rc['zone_name'],
                        'area' => $rc['area_name'],
                        'payment_method' => $rc['payment_method'],
                        'Pieces' => $rc['Pieces'],
                        'Address' => $rc['Address'],
                        'courier_confirm' => $rc['courier_confirm'],
                    ];

                    $confirm_align = $mobile_lang === 'ar' ? 'left' : 'right';
                    $isPicked = mobile_is_picked_status($statusShort, $statusId);
                    $canOpen = ((int) $cur['courier_confirm'] === 1) && !$isPicked;

                    if ($isPicked) {
                        $box_class = 'tbl-item-picked';
                        $btn_content = "<span class='order-picked-badge'>" . mobile_t('status_picked', $mobile_lang) . "</span>";
                        $color = 'color:#e56808';
                    } elseif ($isPickup && (int) $cur['courier_confirm'] === 0) {
                        $box_class = 'tbl-item-pickup';
                        $btn_content = "<input type='button' value='" . mobile_t('confirm', $mobile_lang) . "' class='confirmOr confirmOr--pickup' onclick=\"event.stopPropagation();confirmOrder('" . $rc['AWB'] . "','" . $mobile_domain . "','" . $mobile_token . "');\">";
                        $color = 'color:#e56808';
                    } elseif ($isPickup) {
                        $box_class = 'tbl-item-picked';
                        $btn_content = "<i class='bi bi-check2-circle order-pickup-check' aria-hidden='true'></i>";
                        $color = 'color:#e56808';
                    } elseif ((int) $cur['courier_confirm'] === 0) {
                        $box_class = 'tbl-item';
                        $btn_content = "<input type='button' value='" . mobile_t('confirm', $mobile_lang) . "' class='confirmOr' onclick=\"event.stopPropagation();confirmOrder('" . $rc['AWB'] . "','" . $mobile_domain . "','" . $mobile_token . "');\">";
                        $color = 'color:#0075ff';
                    } else {
                        $box_class = 'tbl-item-confirm';
                        $btn_content = "<img src='imgs/check.png' width='20px'>";
                        $color = 'color:#0075ff';
                    }

                    $tbl .= "<tr><td>";

                    if ($canOpen) {
                        $tbl .= "<a style='text-decoration: none;' href=\"javascript:openOrder('" . $rc['AWB'] . "','" . $mobile_domain . "','" . $mobile_token . "')\">";
                    } else {
                        $tbl .= "<div>";
                    }

                    $tbl .= "<table class='" . $box_class . "' border='0'>
                     <tr>
            <td id='awb' style='" . $color . "'>" . $cur['AWB'] . "</td>
            <td>" . $cur['city'] . "</td>
            <td style='text-align: center;'>" . $cur['area'] . "</td>
                   </tr>

                  <tr>
            <td>" . $cur['Reciver_phone'] . "</td>

            <td>" . $cur['payment_method'] . "</td>
            <td style='text-align: " . $confirm_align . ";'>
                 $btn_content
            </td>
                 </tr>
             </table>
               " . ($canOpen ? "</a>" : "</div>") . "
                </td>
            </tr>
            ";
                }

                $tbl .= "</table>";
            } else {
                $tbl .= "<tr><td align='center'> <br> " . mobile_t('empty_orders', $mobile_lang) . " <br><br> </td></tr>";
            }

            echo $tbl;
        } else {
            echo 9;
        }
    } else {
        echo 4;
    }
}

?>
