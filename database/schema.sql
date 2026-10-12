-- ---------------------------------------------------------------------------
-- NONXSTUDIOS — database schema (MySQL 8 / MariaDB 10.4+)
-- Run with:  mysql -u root -p < database/schema.sql
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
-- Administrators
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','editor') NOT NULL DEFAULT 'admin',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Login throttling
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(190) NOT NULL,
    ip         VARCHAR(45) NOT NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_email_time (email, created_at),
    KEY idx_login_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Portfolio projects
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(190) NOT NULL,
    slug        VARCHAR(190) NOT NULL,
    category    VARCHAR(60) NOT NULL,
    year        SMALLINT UNSIGNED NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    case_study  MEDIUMTEXT NULL,
    software    VARCHAR(255) NOT NULL DEFAULT '',
    thumbnail   VARCHAR(255) NULL,
    video_url   VARCHAR(500) NULL,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    published   TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_slug (slug),
    KEY idx_projects_published (published, sort_order),
    KEY idx_projects_category (category),
    KEY idx_projects_featured (featured, published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Project gallery images
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    path       VARCHAR(255) NOT NULL,
    caption    VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_images_project (project_id, sort_order),
    CONSTRAINT fk_images_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Contact / project inquiries
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inquiries (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    company        VARCHAR(190) NULL,
    service        VARCHAR(120) NULL,
    budget         VARCHAR(60) NULL,
    timeline       VARCHAR(60) NULL,
    description    TEXT NOT NULL,
    contact_method VARCHAR(60) NULL,
    status         ENUM('new','in_progress','completed','archived') NOT NULL DEFAULT 'new',
    internal_notes TEXT NULL,
    ip             VARCHAR(45) NULL,
    user_agent     VARCHAR(255) NULL,
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_inquiries_status (status, created_at),
    KEY idx_inquiries_email (email),
    KEY idx_inquiries_ip_time (ip, created_at),
    KEY idx_inquiries_service (service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Editable site settings (key/value)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
