<?php

function platformConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/platform_config.php';
    }
    return $config;
}

function platformRequest(string $method, string $path, ?array $body = null): array
{
    $config = platformConfig();
    $base = rtrim((string) ($config['api_base'] ?? ''), '/');
    $key = (string) ($config['api_key'] ?? '');

    if ($base === '' || $key === '' || $key === 'CHANGE_ME') {
        return [
            'ok' => false,
            'status' => 0,
            'error' => 'اضبط api_base و api_key في admin/platform_config.php أو platform_config.local.php',
            'data' => null,
        ];
    }

    $url = $base . '/api/v1/platform/' . ltrim($path, '/');
    $headers = [
        'Accept: application/json',
        'X-Platform-Api-Key: ' . $key,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'status' => 0, 'error' => $err ?: 'فشل الاتصال بـ API', 'data' => null];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['ok' => false, 'status' => $code, 'error' => 'استجابة غير صالحة من النظام', 'data' => null];
    }

    return [
        'ok' => $code >= 200 && $code < 300,
        'status' => $code,
        'error' => $json['message'] ?? ($code >= 400 ? ('خطأ HTTP ' . $code) : null),
        'data' => $json,
    ];
}

function platformH(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
