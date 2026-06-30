-- تحديث كلمة مرور الأدmin (admin / Trakmile@2026)
UPDATE `admin_users`
SET `password_hash` = '$2y$10$t4ew9GmdVO.j6CAcynTHzuq7FgNE8UNDT5LO0n5pB5ZYeYounUKbe'
WHERE `username` = 'admin';
