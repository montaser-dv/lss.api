-- Migration: add language field to quote_requests (run once on trak_db)
ALTER TABLE `quote_requests`
  ADD COLUMN `lang` enum('ar','en') NOT NULL DEFAULT 'ar' AFTER `description`,
  ADD KEY `idx_lang` (`lang`);
