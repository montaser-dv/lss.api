# Trakmile — وثائق المشروع

## نظرة عامة

هذا المستودع يضم **الموقع التعريفي** لمنصة **Trakmile** (تراك مايل) — منصة إدارة لوجستية ذكية، مع:

- صفحة هبوط ثنائية اللغة (**عربي افتراضي** + إنجليزي)
- نموذج **طلب استشارة** للعملاء المحتملين
- **لوحة إدارة** لطلبات الاستشارة
- **API عام** للمنصة واستقبال الطلبات
- **mobile-api** لربط تطبيق المندوبين (React Native WebView)
- وثائق المنتج والبروفايلات (عربي/إنجليزي)

> المنصة التشغيلية الكاملة (شحنات، محاسبة، AI، مستودعات) تعمل على `demo.trakmile.com` وقواعد بيانات العملاء المنفصلة.

---

## سير العمل والنشر

```
محلي (Laragon)  →  GitHub  →  الخادم (Ubuntu + CyberPanel)  →  trakmile.com
     برمجة              push         webhook تلقائي                  اونلاين
```

| البيئة | الوصف |
|--------|-------|
| **محلي** | `d:\laragon\www\repos\lss.api` على Laragon |
| **GitHub** | مستودع Git — `push` إلى `main` |
| **الإنتاج** | Ubuntu + CyberPanel — `trakmile.com` |
| **النشر** | webhook → `git_deploy/index.php` → `git fetch` + `git reset --hard origin/main` |

---

## هيكل المشروع

```
lss.api/
├── index.html                  # الصفحة التعريفية (عربي/إنجليزي)
├── robots.txt                  # سياسة الفهرسة
├── sitemap.xml                 # خريطة الموقع
├── js/
│   ├── i18n.js                 # الترجمة (~200 مفتاح)
│   └── app.js                  # اللغة، SEO، نموذج الاستشارة، الشريط السفلي
├── config.php                  # يحمّل api/db.php
├── api/
│   ├── db.php                  # اتصال mysqli ($db)
│   ├── ping.php                # فحص صحة الخدمة
│   ├── platform.php            # بيانات المنصة والميزات (جديد)
│   ├── submit_quote.php        # استقبال طلبات الاستشارة
│   ├── test_db.php             # تشخيص قاعدة البيانات
│   └── setup_db.php            # إعداد الجداول (تطوير فقط)
├── admin/
│   ├── login.php
│   ├── index.php               # إدارة طلبات الاستشارة
│   └── logout.php
├── mobile-api/                 # API تطبيق المندوبين
├── docs/
│   ├── PROJECT.md              # هذا الملف
│   ├── API.md                  # مرجع API كامل
│   ├── Trakmile-Overview.html  # البروفايل العربي
│   ├── Trakmile-Overview-en.html
│   └── DB/
│       ├── quote_tables.sql
│       ├── migrate_quote_lang.sql
│       └── trak_db.sql
└── git_deploy/
    └── index.php
```

---

## الموقع التعريفي (`index.html`)

### الأقسام الرئيسية

| القسم | المعرف | الوصف |
|-------|--------|-------|
| Hero | — | عنوان ذكي، خريطة تتبع مباشرة للمناديب، أزرار استشارة وتجريبي |
| المميزات | `#features` | شحنات، سائقين، أسطول، مستودعات، POD |
| الذكاء الاصطناعي | `#ai` | مساعد AI مدمج (عرض تسويقي) |
| المحاسبة | `#accounting` | نظام محاسبي مدمج (عرض تسويقي) |
| المستودعات | `#warehouses` | مستودعات متعددة المراكز |
| كيف يعمل | `#how-it-works` | خطوات البدء |
| تطبيق الجوال | `#mobile` | مندوبين + باركود |
| الأسئلة الشائعة | `#faq` | FAQ |
| التواصل | `#contact` | CTA ونموذج |

### اللغات وSEO

- افتراضي: **العربية** (RTL) — تبديل عبر زر EN أو `?lang=en`
- حفظ اللغة في `localStorage`
- وسوم SEO: description, keywords, Open Graph, Twitter, JSON-LD
- `hreflang` + `canonical` + `robots.txt` + `sitemap.xml`
- تحديث ديناميكي للـ meta عند تبديل اللغة (`updateSeoMeta` في `app.js`)

### نموذج طلب استشارة

- زر **«طلب استشارة»** في الهيدر، Hero، الجوال، والشريط السفلي
- شريط سفلي للجوال يظهر بعد اختفاء زر Hero (`initStickyQuote`)
- الحقول: الاسم، الجوال، البريد، الوصف + **لغة الواجهة** (`lang`)
- الإرسال إلى `api/submit_quote.php` → جدول `quote_requests`

---

## API العام (`api/`)

مرجع تفصيلي: **[docs/API.md](API.md)**

| الملف | الطريقة | الوظيفة |
|-------|---------|---------|
| `ping.php` | GET | فحص صحة + إصدار API |
| `platform.php` | GET | ميزات المنصة، تكاملات، نقاط النهاية |
| `submit_quote.php` | POST | طلب استشارة |
| `test_db.php` | GET | تشخيص DB (تطوير) |
| `setup_db.php` | GET | إنشاء الجداول (تطوير — لا تُعرّض في الإنتاج) |

---

## لوحة الإدارة (`/admin/`)

| الرابط | الوصف |
|--------|-------|
| `/admin/` | تسجيل الدخول |
| `/admin/index.php` | طلبات الاستشارة (فلترة: جديد / مقروء / تم التواصل) |

### بيانات الدخول الافتراضية

| الحقل | القيمة |
|-------|--------|
| اسم المستخدم | `admin` |
| كلمة المرور | `Trakmile@2026` |

> غيّر كلمة المرور بعد أول دخول.

---

## قاعدة البيانات

### إعداد أولي

```bash
mysql -u trak_user -p trak_db < docs/DB/quote_tables.sql
```

### ترقية موجودة (حقل اللغة)

```bash
mysql -u trak_user -p trak_db < docs/DB/migrate_quote_lang.sql
```

### جدول `quote_requests`

| العمود | الوصف |
|--------|-------|
| `name` | اسم العميل |
| `phone` | جوال سعودي (`5xxxxxxxx`) |
| `email` | البريد |
| `description` | الوصف |
| `lang` | `ar` / `en` — لغة واجهة الموقع عند الإرسال |
| `status` | `new` / `read` / `contacted` |
| `created_at` | التاريخ |

### جدول `domains` (`trak_db.sql`)

سجل النطاقات الفرعية للعملاء وربطها بقواعد بياناتهم (لـ `mobile-api`).

---

## تطبيق الجوال (`mobile-api/`)

تكامل مع React Native عبر **WebView** + `postMessage`.

| الملف | الوظيفة |
|-------|---------|
| `check_domain.php` | التحقق من النطاق |
| `login.php` | دخول المندوب |
| `home.php` | إحصائيات لوحة المندوب |
| `getOrders.php` | الطلبات النشطة |
| `getOrdershistory.php` | الأرشيف |
| `openorder.php` | تفاصيل الطلب + خريطة |
| `confirmOrder.php` | تأكيد استلام |
| `confirmOrderApi.php` | تأكيد بالباركود |
| `orderAction.php` | تسليم / عدم تسليم |
| `update_password.php` | تغيير كلمة المرور |

---

## البروفايلات التعريفية

| الملف | اللغة |
|-------|-------|
| `docs/Trakmile-Overview.html` / `.pdf` | عربي |
| `docs/Trakmile-Overview-en.html` / `.pdf` | إنجليزي |

العنوان الإنجليزي: **Trakmile: Smart Logistics Management Platform**

---

## النشر (`git_deploy`)

- **المسار:** `/home/trakmile.com/public_html`
- **Webhook:** GitHub → `git_deploy/index.php`
- **السجلات:** `deploy.log`, `deploy_error.log`

### التحقق بعد النشر

1. View Source → `LANDING-SINGLE:v6` مرة واحدة فقط
2. `https://trakmile.com/api/ping.php`
3. `https://trakmile.com/api/platform.php`
4. `https://trakmile.com/robots.txt`

---

## إصدار API

| الإصدار | التاريخ | ملاحظات |
|---------|---------|---------|
| **2.0** | 2026-07-09 | `platform.php`، حقل `lang`، توثيق كامل، تحديث الاستشارة |
| 1.0 | 2026-06 | طلبات العروض + admin + mobile-api أساسي |
