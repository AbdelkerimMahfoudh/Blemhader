-- Ticker (trending breaking news, expires after 10 min)
CREATE TABLE IF NOT EXISTS ticker_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  text_ar VARCHAR(500) NOT NULL,
  text_fr VARCHAR(500) NOT NULL,
  url VARCHAR(500) DEFAULT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
