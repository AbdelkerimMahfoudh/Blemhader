-- Add sort_order to articles for manual ordering
ALTER TABLE articles ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured;
