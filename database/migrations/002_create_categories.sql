-- Ensure categories table and seed data exist (run if you get "Table categories doesn't exist")
-- Usage: mysql -u root -p blemhader < database/migrations/002_create_categories.sql

USE blemhader;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(50) NOT NULL UNIQUE,
  name_ar VARCHAR(100) NOT NULL,
  name_fr VARCHAR(100) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (slug, name_ar, name_fr, sort_order) VALUES
('home', 'الرئيسية', 'Accueil', 0),
('live', 'البثوث المباشرة', 'Les directs', 1),
('meetings', 'لقاءات', 'Rencontres', 2),
('news', 'أخبار', 'Actualités', 3),
('economy', 'اقتصاد', 'Économie', 4),
('documentaries', 'وثائقيات', 'Documentaires', 5)
ON DUPLICATE KEY UPDATE name_ar = VALUES(name_ar), name_fr = VALUES(name_fr), sort_order = VALUES(sort_order);
