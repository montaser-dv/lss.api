# Trakmile — وثائق المشروع

## نظرة عامة

هذا الموقع هو **موقع تعريفي** لمنصة **Trakmile** (تراك مايل) — منصة إدارة لوجستية ذكية.

المشروع يتكون من:

- صفحة تعريفية ثنائية اللغة (`index.html`) — **عربي (افتراضي)** + إنجليزي
- نموذج **طلب عرض** للعملاء
- **لوحة إدارة** لاستعراض طلبات العروض
- واجهات API لربط **تطبيق الجوال** (مجلد `mobile-api`)
- وثائق النظام ونسخة من قاعدة البيانات (مجلد `docs`)

---

## سير عمل التطوير والنشر

```
محلي (Laragon)  →  GitHub  →  الخادم (Ubuntu + CyberPanel)  →  trakmile.com
     برمجة              push         webhook تلقائي                  اونلاين
```

| البيئة | الوصف |
|--------|-------|
| **محلي** | التطوير على Laragon في Windows (`d:\laragon\www\repos\lss.api`) |
| **GitHub** | مستودع Git مربوط بالمشروع — يُرفع إليه الكود بعد الانتهاء |
| **الإنتاج** | خادم Ubuntu مع لوحة **CyberPanel** — الموقع live على `trakmile.com` |
| **النشر** | عند `push` إلى `main`، GitHub يرسل webhook إلى `git_deploy/index.php` فينفّذ `git fetch` + `git reset --hard origin/main` تلقائياً |

### خطوات العمل اليومية

1. البرمجة والمعاينة محلياً على Laragon
2. `git add` → `git commit` → `git push` إلى GitHub
3. GitHub webhook ينشر التحديثات على الخادم تلقائياً
4. استعراض النتيجة مباشرة على الموقع الحي

> **ملاحظة:** إعداد قاعدة البيانات (مثل `quote_tables.sql`) يُنفَّذ على **خادم الإنتاج** في قاعدة `trak_db` عبر CyberPanel/phpMyAdmin.

---

## هيكل المشروع

```
lss.api/
├── index.html              # الصفحة التعريفية (عربي/إنجليزي)
├── js/
│   ├── i18n.js             # ملف الترجمة
│   └── app.js              # تبديل اللغة + نموذج طلب العرض
├── config.php              # يحمّل api/db.php
├── api/
│   ├── db.php              # اتصال قاعدة البيانات ($db)
│   ├── submit_quote.php    # استقبال طلبات العروض
│   ├── test_db.php         # اختبار الاتصال
│   └── ping.php            # اختبار PHP
├── admin/
│   ├── login.php           # تسجيل دخول الأدمن
│   ├── index.php           # استعراض طلبات العروض
│   └── logout.php
├── mobile-api/             # API ربط تطبيق الجوال
├── docs/
│   ├── PROJECT.md
│   └── DB/
│       ├── trak_db.sql
│       └── quote_tables.sql  # جداول طلبات العروض والأدمن
└── git_deploy/
    └── index.php           # webhook النشر التلقائي من GitHub
```

---

## الموقع التعريفي

### اللغات

- اللغة الافتراضية: **العربية** (RTL)
- زر **EN / عربي** في الشريط العلوي للتبديل
- يتم حفظ اختيار اللغة في `localStorage`

### نموذج طلب عرض

- زر **"طلب عرض"** في الشريط العلوي والصفحة الرئيسية وقسم CTA
- الحقول: الاسم، رقم الجوال، البريد الإلكتروني، الوصف
- يُرسل إلى `api/submit_quote.php` ويُخزَّن في جدول `quote_requests`

---

## لوحة الإدارة

| الرابط | الوصف |
|--------|-------|
| `/admin/login.php` | تسجيل الدخول |
| `/admin/index.php` | استعراض وإدارة الطلبات |

### بيانات الدخول الافتراضية

| الحقل | القيمة |
|-------|--------|
| اسم المستخدم | `admin` |
| كلمة المرور | `Trakmile@2026` |

> **مهم:** غيّر كلمة المرور بعد أول دخول.

### إعداد قاعدة البيانات

نفّذ ملف SQL على قاعدة `trak_db`:

```bash
mysql -u trak_user -p trak_db < docs/DB/quote_tables.sql
```

> **ملاحظة:** جميع ملفات PHP تستخدم `config.php` في جذر المشروع ومتغير `$db`.

---

## قاعدة البيانات

### جدول `quote_requests`

| العمود | الوصف |
|--------|-------|
| `name` | اسم العميل |
| `phone` | رقم الجوال |
| `email` | البريد الإلكتروني |
| `description` | الوصف |
| `status` | `new` / `read` / `contacted` |
| `created_at` | تاريخ الإرسال |

### جدول `admin_users`

مستخدمو لوحة الإدارة.

---

## تطبيق الجوال (`mobile-api`)

تتواصل نقاط API مع تطبيق React Native عبر **WebView**.

| الملف | الوظيفة |
|-------|---------|
| `check_domain.php` | التحقق من النطاق الفرعي |
| `login.php` | تسجيل الدخول |
| `home.php` | بيانات لوحة التحكم |
| `getOrders.php` | جلب الطلبات |

---

## النشر (`git_deploy`)

- **الخادم:** Ubuntu + CyberPanel
- **المسار:** `/home/trakmile.com/public_html`
- **الآلية:** GitHub webhook → `git_deploy/index.php` → `git fetch` + `git reset --hard origin/main`
- **السجلات:** `git_deploy/deploy.log` و `git_deploy/deploy_error.log`
