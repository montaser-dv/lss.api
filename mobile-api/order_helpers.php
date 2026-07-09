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

if (!function_exists('mobile_get_order_type_from_row')) {
    function mobile_get_order_type_from_row(array $row) {
        foreach (['client_access_type_id', 'order_type', 'Order_type', 'Type', 'type', 'service_type', 'Service_type'] as $column) {
            if (!empty($row[$column])) {
                return mobile_normalize_order_type($row[$column]);
            }
        }

        return '';
    }
}

if (!function_exists('mobile_orders_client_join_sql')) {
    function mobile_orders_client_join_sql() {
        return 'LEFT JOIN clients c ON c.user_id = u.id';
    }
}

if (!function_exists('mobile_find_status_id')) {
    function mobile_find_status_id(mysqli $db, $statusName) {
        $statusName = strtolower(trim((string) $statusName));
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
                    $sql = "SELECT `$idCol` AS sid FROM `$table` WHERE LOWER(`$nameCol`) = ? LIMIT 1";
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
                return strtolower(trim((string) $row[$column]));
            }
        }

        $statusId = $row['Status'] ?? $row['status'] ?? null;
        if ($statusId === null || $statusId === '') {
            return '';
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
                    $stmt->bind_param('s', $statusId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $name = strtolower(trim((string) $result->fetch_assoc()['sname']));
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

        return $fallback[(int) $statusId] ?? (string) $statusId;
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
        $statusName = strtolower(trim((string) $statusName));
        $key = 'status_' . str_replace(' ', '_', $statusName);
        $translated = mobile_t($key, $lang);
        if ($translated !== $key) {
            return $translated;
        }
        return $statusName !== '' ? ucwords(str_replace('_', ' ', $statusName)) : mobile_t('status_unknown', $lang);
    }
}

if (!function_exists('mobile_should_show_picked_action')) {
    function mobile_should_show_picked_action($orderType, $statusName) {
        return mobile_normalize_order_type($orderType) === 'last_mile'
            && strtolower(trim((string) $statusName)) === 'created';
    }
}
