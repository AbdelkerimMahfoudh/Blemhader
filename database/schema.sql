-- Blemhader database schema
-- Run this in MySQL/MariaDB (e.g. phpMyAdmin or: mysql -u root -p < database/schema.sql)

CREATE DATABASE IF NOT EXISTS blemhader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blemhader;

-- Categories (nav sections: home, live, meetings, news, economy, documentaries)
CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(50) NOT NULL UNIQUE,
  name_ar VARCHAR(100) NOT NULL,
  name_fr VARCHAR(100) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Articles / content (news, economy, meetings, documentaries, hero items)
CREATE TABLE articles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title_ar VARCHAR(255) NOT NULL,
  title_fr VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  excerpt_ar TEXT,
  excerpt_fr TEXT,
  body_ar LONGTEXT,
  body_fr LONGTEXT,
  image_url VARCHAR(500) DEFAULT NULL,
  video_url VARCHAR(500) DEFAULT NULL,
  read_minutes TINYINT UNSIGNED DEFAULT NULL,
  published_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_category (category_id),
  KEY idx_published (published_at),
  KEY idx_status (status),
  KEY idx_featured (is_featured),
  KEY idx_category_published (category_id, published_at),
  CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hero / carousel items (videos or featured items for the main card)
CREATE TABLE hero_slides (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_ar VARCHAR(80) NOT NULL,
  category_fr VARCHAR(80) NOT NULL,
  title_ar VARCHAR(255) NOT NULL,
  title_fr VARCHAR(255) NOT NULL,
  meta_ar VARCHAR(120) DEFAULT NULL,
  meta_fr VARCHAR(120) DEFAULT NULL,
  video_url VARCHAR(500) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticker (breaking news strip)
CREATE TABLE ticker_items (
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

-- Admin users (for add/edit/delete content)
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed categories (match nav order: الرئيسية, البثوث المباشرة, لقاءات, أخبار, اقتصاد, وثائقيات)
INSERT INTO categories (slug, name_ar, name_fr, sort_order) VALUES
('home', 'الرئيسية', 'Accueil', 0),
('live', 'البثوث المباشرة', 'Les directs', 1),
('meetings', 'لقاءات', 'Rencontres', 2),
('news', 'أخبار', 'Actualités', 3),
('economy', 'اقتصاد', 'Économie', 4),
('documentaries', 'وثائقيات', 'Documentaires', 5);
