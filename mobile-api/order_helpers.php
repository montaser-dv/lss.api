<?php

if (!function_exists('mobile_normalize_order_type')) {
    function mobile_normalize_order_type($value) {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', ' '], '_', $value);

        if (in_array($value, ['last_mile', 'lastmile'], true)) {
            return 'last_mile';
        }
        if (in_array($value, ['fulfillment', 'fullfilment'], true)) {
            return 'fulfillment';
        }

        return $value;
    }
}

if (!function_exists('mobile_normalize_status_name')) {
    function mobile_normalize_status_name($value) {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', ' '], '_', $value);

        $aliases = [
            'new' => 'created',
            'assign' => 'assigned',
            'assigned_to_courier' => 'assigned',
            'courier_assigned' => 'assigned',
        ];

        return $aliases[$value] ?? $value;
    }
}

if (!function_exists('mobile_orders_client_join_sql')) {
    function mobile_orders_client_join_sql() {
        return 'LEFT JOIN clients c ON (c.user_id = o.Brand OR c.user_id = u.id OR c.ID = o.Brand)';
    }
}

if (!function_exists('mobile_orders_client_select_sql')) {
    function mobile_orders_client_select_sql() {
        return 'c.client_access_type_id AS client_order_type, c.business_name AS client_business_name';
    }
}

if (!function_exists('mobile_parse_access_type_value')) {
    function mobile_parse_access_type_value(mysqli $db, $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        if (!ctype_digit($raw)) {
            $normalized = mobile_normalize_order_type($raw);
            if ($normalized === 'last_mile' || $normalized === 'fulfillment') {
                return $normalized;
            }
        }

        $tables = ['client_access_types', 'client_access_type', 'access_types', 'access_type'];
        $idColumns = ['ID', 'id'];
        $nameColumns = ['name', 'Name', 'title', 'type_name'];

        foreach ($tables as $table) {
            $check = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
            if (!$check || $check->num_rows === 0) {
                continue;
            }

            foreach ($idColumns as $idCol) {
                foreach ($nameColumns as $nameCol) {
                    $sql = "SELECT `$nameCol` AS type_name FROM `$table` WHERE `$idCol` = ? LIMIT 1";
                    $stmt = $db->prepare($sql);
                    if (!$stmt) {
                        continue;
                    }
                    $stmt->bind_param('s', $raw);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $name = mobile_normalize_order_type($result->fetch_assoc()['type_name']);
                        $stmt->close();
                        if ($name === 'last_mile' || $name === 'fulfillment') {
                            return $name;
                        }
                    }
                    $stmt->close();
                }
            }
        }

        return mobile_normalize_order_type($raw);
    }
}

if (!function_exists('mobile_lookup_client_order_type_by_brand')) {
    function mobile_lookup_client_order_type_by_brand(mysqli $db, $brand) {
        $brand = (int) $brand;
        if ($brand <= 0) {
            return '';
        }

        $stmt = $db->prepare('SELECT client_access_type_id FROM clients WHERE user_id = ? OR ID = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }

        $stmt->bind_param('ii', $brand, $brand);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            $stmt->close();
            return '';
        }

        $raw = $result->fetch_assoc()['client_access_type_id'] ?? '';
        $stmt->close();

        return mobile_parse_access_type_value($db, $raw);
    }
}

if (!function_exists('mobile_resolve_client_order_type')) {
    function mobile_resolve_client_order_type(mysqli $db, array $row) {
        if (!empty($row['client_order_type'])) {
            $type = mobile_parse_access_type_value($db, $row['client_order_type']);
            if ($type === 'last_mile' || $type === 'fulfillment') {
                return $type;
            }
        }

        $brand = $row['Brand'] ?? null;
        if ($brand !== null && $brand !== '') {
            $type = mobile_lookup_client_order_type_by_brand($db, $brand);
            if ($type === 'last_mile' || $type === 'fulfillment') {
                return $type;
            }
        }

        return '';
    }
}

if (!function_exists('mobile_get_order_type_from_row')) {
    function mobile_get_order_type_from_row(array $row, ?mysqli $db = null) {
        if ($db instanceof mysqli) {
            return mobile_resolve_client_order_type($db, $row);
        }

        foreach (['client_order_type', 'client_access_type_id', 'order_type', 'Order_type', 'Type', 'type', 'service_type', 'Service_type'] as $column) {
            if (!empty($row[$column])) {
                return mobile_normalize_order_type($row[$column]);
            }
        }

        return '';
    }
}

if (!function_exists('mobile_find_status_id')) {
    function mobile_find_status_id(mysqli $db, $statusName) {
        $statusName = mobile_normalize_status_name($statusName);
        if ($statusName === '') {
            return null;
        }

        $tables = ['statuses', 'order_status', 'order_statuses', 'status'];
        $idColumns = ['ID', 'id'];
        $nameColumns = ['name', 'Name', 'status_name', 'title'];

        foreach ($tables as $table) {
            $check = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
            if (!$check || $check->num_rows === 0) {
                continue;
            }

            foreach ($idColumns as $idCol) {
                foreach ($nameColumns as $nameCol) {
                    $sql = "SELECT `$idCol` AS sid FROM `$table` WHERE LOWER(REPLACE(REPLACE(`$nameCol`, '-', '_'), ' ', '_')) = ? LIMIT 1";
                    $stmt = $db->prepare($sql);
                    if (!$stmt) {
                        continue;
                    }
                    $stmt->bind_param('s', $statusName);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $id = (int) $result->fetch_assoc()['sid'];
                        $stmt->close();
                        return $id > 0 ? $id : null;
                    }
                    $stmt->close();
                }
            }
        }

        $fallback = [
            'created' => 1,
            'picked' => 2,
            'delivered' => 7,
            'not_delivered' => 13,
        ];

        return $fallback[$statusName] ?? null;
    }
}

if (!function_exists('mobile_get_status_name_from_row')) {
    function mobile_get_status_name_from_row(mysqli $db, array $row) {
        foreach (['status_name', 'Status_name', 'status_label'] as $column) {
            if (!empty($row[$column])) {
                return mobile_normalize_status_name($row[$column]);
            }
        }

        $statusValue = $row['Status'] ?? $row['status'] ?? null;
        if ($statusValue === null || $statusValue === '') {
            return '';
        }

        $statusValue = trim((string) $statusValue);
        if ($statusValue === '') {
            return '';
        }

        if (!ctype_digit($statusValue)) {
            return mobile_normalize_status_name($statusValue);
        }

        $tables = ['statuses', 'order_status', 'order_statuses', 'status'];
        $idColumns = ['ID', 'id'];
        $nameColumns = ['name', 'Name', 'status_name', 'title'];

        foreach ($tables as $table) {
            $check = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
            if (!$check || $check->num_rows === 0) {
                continue;
            }

            foreach ($idColumns as $idCol) {
                foreach ($nameColumns as $nameCol) {
                    $sql = "SELECT `$nameCol` AS sname FROM `$table` WHERE `$idCol` = ? LIMIT 1";
                    $stmt = $db->prepare($sql);
                    if (!$stmt) {
                        continue;
                    }
                    $stmt->bind_param('s', $statusValue);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $name = mobile_normalize_status_name($result->fetch_assoc()['sname']);
                        $stmt->close();
                        return $name;
                    }
                    $stmt->close();
                }
            }
        }

        $fallback = [
            1 => 'created',
            2 => 'picked',
            7 => 'delivered',
            13 => 'not_delivered',
        ];

        return $fallback[(int) $statusValue] ?? mobile_normalize_status_name($statusValue);
    }
}

if (!function_exists('mobile_order_type_label')) {
    function mobile_order_type_label($type, $lang = null) {
        $type = mobile_normalize_order_type($type);
        if ($type === 'last_mile') {
            return mobile_t('order_type_last_mile', $lang);
        }
        if ($type === 'fulfillment') {
            return mobile_t('order_type_fulfillment', $lang);
        }
        return $type !== '' ? ucwords(str_replace('_', ' ', $type)) : mobile_t('order_type_unknown', $lang);
    }
}

if (!function_exists('mobile_status_label')) {
    function mobile_status_label($statusName, $lang = null) {
        $statusName = mobile_normalize_status_name($statusName);
        $key = 'status_' . str_replace(' ', '_', $statusName);
        $translated = mobile_t($key, $lang);
        if ($translated !== $key) {
            return $translated;
        }
        return $statusName !== '' ? ucwords(str_replace('_', ' ', $statusName)) : mobile_t('status_unknown', $lang);
    }
}

if (!function_exists('mobile_is_post_pickup_status')) {
    function mobile_is_post_pickup_status($statusName) {
        return in_array(mobile_normalize_status_name($statusName), [
            'picked',
            'delivered',
            'not_delivered',
            'cancelled',
            'canceled',
            'returned',
            'closed',
            'completed',
            'archived',
        ], true);
    }
}

if (!function_exists('mobile_is_pre_pickup_status')) {
    function mobile_is_pre_pickup_status($statusName) {
        $status = mobile_normalize_status_name($statusName);
        if ($status === '') {
            return false;
        }

        return !mobile_is_post_pickup_status($status);
    }
}

if (!function_exists('mobile_should_show_picked_action')) {
    function mobile_should_show_picked_action($orderType, $statusName) {
        return mobile_normalize_order_type($orderType) === 'last_mile'
            && mobile_is_pre_pickup_status($statusName);
    }
}
