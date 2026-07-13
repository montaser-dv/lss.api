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

/**
 * @param array $source ticket array or folder with optional company
 * @return array{name:string,domain:?string,url:?string,c_code:?string,email:?string,owner:?string,mobile:?string,city:?string,address:?string,tickets:?int,open:?int,new:?int}
 */
function platformCompanyBannerData(array $source, ?array $folderStats = null): array
{
    $company = is_array($source['company'] ?? null) ? $source['company'] : [];
    $name = trim((string) ($company['name'] ?? ''));
    if ($name === '') {
        $name = trim((string) ($source['label'] ?? $source['origin_label'] ?? $source['origin_subdomain'] ?? ''));
    }
    if ($name === '') {
        $name = 'شركة التوصيل';
    }

    $domain = $company['domain'] ?? ($source['origin_subdomain'] ?? ($source['label'] ?? null));
    $url = $company['url'] ?? ($source['origin_url'] ?? ($source['url'] ?? null));
    $cCode = $company['c_code'] ?? ($source['c_code'] ?? null);

    return [
        'name' => $name,
        'domain' => $domain ? (string) $domain : null,
        'url' => $url ? (string) $url : null,
        'c_code' => $cCode ? (string) $cCode : null,
        'email' => isset($company['email']) ? (string) $company['email'] : null,
        'owner' => isset($company['owner_name']) ? (string) $company['owner_name'] : null,
        'mobile' => isset($company['owner_mobile']) ? (string) $company['owner_mobile'] : null,
        'city' => isset($company['city']) ? (string) $company['city'] : null,
        'address' => isset($company['address']) ? (string) $company['address'] : null,
        'tickets' => isset($folderStats['count']) ? (int) $folderStats['count'] : null,
        'open' => isset($folderStats['open']) ? (int) $folderStats['open'] : null,
        'new' => isset($folderStats['new']) ? (int) $folderStats['new'] : null,
    ];
}

function platformCompanyBannerCss(): string
{
    return <<<'CSS'
.company-banner {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    padding: 22px 24px;
    margin-bottom: 22px;
    color: #fff;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,.18), transparent 40%),
        linear-gradient(135deg, #0B1220 0%, #123A6D 48%, #1B84FF 100%);
    box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
}
.company-banner::after {
    content: "";
    position: absolute;
    inset: auto -40px -50px auto;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.company-banner-top {
    display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.company-kicker {
    display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase; opacity: .8; margin-bottom: 8px;
}
.company-name { font-size: 28px; font-weight: 800; line-height: 1.2; margin-bottom: 6px; }
.company-domain {
    display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18);
    padding: 6px 12px; border-radius: 999px; margin-top: 8px; flex-wrap: wrap;
}
.company-domain a { color: #fff; text-decoration: none; }
.company-domain a:hover { text-decoration: underline; }
.company-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.company-stat {
    min-width: 84px; text-align: center; background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16); border-radius: 14px; padding: 10px 12px;
}
.company-stat strong { display: block; font-size: 20px; font-weight: 800; }
.company-stat span { font-size: 12px; opacity: .85; }
.company-grid {
    position: relative; z-index: 1; margin-top: 18px;
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;
}
@media (max-width: 900px) {
    .company-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .company-name { font-size: 22px; }
}
@media (max-width: 560px) {
    .company-grid { grid-template-columns: 1fr; }
}
.company-chip {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14);
    border-radius: 12px; padding: 10px 12px;
}
.company-chip label { display: block; font-size: 11px; opacity: .75; margin-bottom: 4px; }
.company-chip div { font-size: 13px; font-weight: 700; word-break: break-word; }
.company-chip a { color: #fff; }
CSS;
}

function platformRenderCompanyBanner(array $info): void
{
    ?>
    <section class="company-banner">
        <div class="company-banner-top">
            <div>
                <div class="company-kicker">شركة التوصيل</div>
                <div class="company-name"><?= platformH($info['name']) ?></div>
                <?php if (!empty($info['domain']) || !empty($info['url'])): ?>
                    <div class="company-domain">
                        <?php if (!empty($info['domain'])): ?>
                            <span><?= platformH($info['domain']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($info['url'])): ?>
                            <a href="<?= platformH($info['url']) ?>" target="_blank" rel="noopener"><?= platformH($info['url']) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($info['tickets'] !== null || $info['open'] !== null || $info['new'] !== null): ?>
                <div class="company-stats">
                    <?php if ($info['tickets'] !== null): ?>
                        <div class="company-stat"><strong><?= (int) $info['tickets'] ?></strong><span>التذاكر</span></div>
                    <?php endif; ?>
                    <?php if ($info['open'] !== null): ?>
                        <div class="company-stat"><strong><?= (int) $info['open'] ?></strong><span>مفتوحة</span></div>
                    <?php endif; ?>
                    <?php if ($info['new'] !== null): ?>
                        <div class="company-stat"><strong><?= (int) $info['new'] ?></strong><span>جديدة</span></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="company-grid">
            <?php if (!empty($info['c_code'])): ?>
                <div class="company-chip"><label>كود الشركة</label><div><?= platformH($info['c_code']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($info['owner'])): ?>
                <div class="company-chip"><label>المالك</label><div><?= platformH($info['owner']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($info['mobile'])): ?>
                <div class="company-chip"><label>الجوال</label><div><a href="tel:<?= platformH($info['mobile']) ?>"><?= platformH($info['mobile']) ?></a></div></div>
            <?php endif; ?>
            <?php if (!empty($info['email'])): ?>
                <div class="company-chip"><label>البريد</label><div><a href="mailto:<?= platformH($info['email']) ?>"><?= platformH($info['email']) ?></a></div></div>
            <?php endif; ?>
            <?php if (!empty($info['city'])): ?>
                <div class="company-chip"><label>المدينة</label><div><?= platformH($info['city']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($info['address'])): ?>
                <div class="company-chip"><label>العنوان</label><div><?= platformH($info['address']) ?></div></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
