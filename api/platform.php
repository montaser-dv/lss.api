<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lang = $_GET['lang'] ?? 'ar';
if ($lang !== 'en') {
    $lang = 'ar';
}

$copy = [
    'ar' => [
        'name' => 'Trakmile',
        'tagline' => 'تراك مايل — منصة إدارة لوجستية ذكية',
        'description' => 'منصة سحابية متكاملة لإدارة الشحنات والسائقين والأسطول والمستودعات والمحاسبة والذكاء الاصطناعي.',
        'consultation' => 'طلب استشارة',
    ],
    'en' => [
        'name' => 'Trakmile',
        'tagline' => 'Trakmile: Smart Logistics Management Platform',
        'description' => 'Integrated cloud platform for shipments, drivers, fleet, warehouses, accounting, and AI-assisted operations.',
        'consultation' => 'Request Consultation',
    ],
];

$t = $copy[$lang];

echo json_encode([
    'ok' => true,
    'api_version' => '2.0',
    'site_version' => 'LANDING-SINGLE:v6',
    'updated' => '2026-07-09',
    'platform' => [
        'name' => $t['name'],
        'tagline' => $t['tagline'],
        'description' => $t['description'],
        'website' => 'https://trakmile.com',
        'demo' => 'https://demo.trakmile.com',
        'contact_email' => 'info@trakmile.com',
        'region' => 'MENA',
        'languages' => ['ar', 'en'],
        'default_language' => 'ar',
    ],
    'features' => [
        ['id' => 'shipments', 'ar' => 'إدارة الشحنات', 'en' => 'Shipment Management'],
        ['id' => 'drivers', 'ar' => 'إدارة السائقين والمناديب', 'en' => 'Driver Management'],
        ['id' => 'fleet', 'ar' => 'تتبع الأسطول المباشر', 'en' => 'Live Fleet Tracking'],
        ['id' => 'warehouses', 'ar' => 'مستودعات متطورة متعددة المراكز', 'en' => 'Advanced Multi-Hub Warehouses'],
        ['id' => 'accounting', 'ar' => 'نظام محاسبي مدمج', 'en' => 'Built-in Accounting'],
        ['id' => 'ai', 'ar' => 'مساعد الذكاء الاصطناعي', 'en' => 'AI Assistant'],
        ['id' => 'mobile', 'ar' => 'تطبيق جوال للمندوبين مع باركود', 'en' => 'Driver Mobile App with Barcode'],
        ['id' => 'cod', 'ar' => 'إدارة COD والتحصيلات', 'en' => 'COD Management'],
        ['id' => 'pod', 'ar' => 'إثبات التسليم', 'en' => 'Proof of Delivery'],
        ['id' => 'integrations', 'ar' => 'تكامل المتاجر والأنظمة', 'en' => 'Store & System Integrations'],
    ],
    'integrations' => ['Salla', 'Zid', 'Shopify', 'WooCommerce', 'RESTful API', 'SMS', 'WhatsApp'],
    'stats' => [
        'shipments_managed' => '10M+',
        'clients' => '500+',
        'drivers' => '50K+',
        'uptime' => '99.9%',
    ],
    'endpoints' => [
        'public' => [
            ['path' => '/api/ping.php', 'method' => 'GET', 'description' => 'Health check'],
            ['path' => '/api/platform.php', 'method' => 'GET', 'description' => 'Platform metadata (this endpoint)'],
            ['path' => '/api/submit_quote.php', 'method' => 'POST', 'description' => $t['consultation']],
        ],
        'mobile' => [
            ['path' => '/mobile-api/check_domain.php', 'method' => 'GET'],
            ['path' => '/mobile-api/login.php', 'method' => 'GET'],
            ['path' => '/mobile-api/home.php', 'method' => 'GET'],
            ['path' => '/mobile-api/getOrders.php', 'method' => 'POST'],
            ['path' => '/mobile-api/getOrdershistory.php', 'method' => 'POST'],
            ['path' => '/mobile-api/openorder.php', 'method' => 'GET'],
            ['path' => '/mobile-api/confirmOrder.php', 'method' => 'POST'],
            ['path' => '/mobile-api/confirmOrderApi.php', 'method' => 'GET'],
            ['path' => '/mobile-api/orderAction.php', 'method' => 'POST'],
            ['path' => '/mobile-api/update_password.php', 'method' => 'GET'],
        ],
        'admin' => [
            ['path' => '/admin/', 'description' => 'Admin login & consultation requests'],
            ['path' => '/admin/tickets.php', 'description' => 'Platform technical support tickets'],
        ],
        'domains' => [
            ['path' => '/api/increment_orders.php', 'method' => 'POST', 'description' => 'Increment domains.current_total_orders for a tenant'],
        ],
    ],
    'seo' => [
        'robots' => 'https://trakmile.com/robots.txt',
        'sitemap' => 'https://trakmile.com/sitemap.xml',
        'profiles' => [
            'ar' => 'https://trakmile.com/docs/Trakmile-Overview.pdf',
            'en' => 'https://trakmile.com/docs/Trakmile-Overview-en.pdf',
        ],
    ],
    'docs' => [
        'project' => 'docs/PROJECT.md',
        'api' => 'docs/API.md',
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
