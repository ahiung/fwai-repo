-- ==============================================================================
-- BLUEPRINT TABEL IDEAL (Contek untuk buat tabel baru)
-- ==============================================================================
-- CREATE TABLE nama_tabel (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     ... (kolom spesifik) ...
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     deleted_at TIMESTAMP NULL DEFAULT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ==============================================================================

/**
 * Tanggal Pembuatan: 2026-05-29
 * Nama Pembuat: AI Software Engineer & Security Expert
 * File: 001_init_rbac.sql
 * Deskripsi: Skema database awal untuk IAM (Identity Access Management) berbasis RBAC,
 *            konfigurasi setting aplikasi global, dan sistem audit trail log.
 */

-- Mulai transaksi database
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. TABEL ROLES
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABEL PERMISSIONS
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TABEL ROLE_PERMISSIONS (Pivot/Relasi M-to-M)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `role_permission_unique` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABEL USERS
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `role_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABEL SETTINGS
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. TABEL AUDIT_TRAILS
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_trails`;
CREATE TABLE `audit_trails` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_audit_trails_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- SEED INITIAL DATA (DATA AWAL)
-- ------------------------------------------------------------------------------

-- Seed Peran (Roles)
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Superadmin', 'Memiliki kendali penuh atas semua modul di sistem tanpa batas.'),
(2, 'Admin', 'Mengelola manajemen operasional harian sistem.'),
(3, 'User', 'Pengguna operasional dengan hak akses standar.');

-- Seed Hak Akses (Permissions)
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
-- Manajemen User
(1, 'view_users', 'Melihat daftar dan detail data user.'),
(2, 'add_users', 'Menambah data user baru.'),
(3, 'edit_users', 'Mengubah data user yang ada.'),
(4, 'delete_users', 'Menghapus data user (soft delete).'),
-- Manajemen Peran (Roles)
(5, 'view_roles', 'Melihat daftar peran dan hak akses.'),
(6, 'add_roles', 'Menambah peran baru.'),
(7, 'edit_roles', 'Mengubah konfigurasi peran dan matriks hak akses.'),
(8, 'delete_roles', 'Menghapus peran dari sistem.'),
-- Audit System
(9, 'view_audit_trails', 'Melihat log aktivitas audit trail sistem.');

-- Bind Peran Superadmin ke semua Hak Akses (Permissions)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), -- Superadmin gets all
(2, 1), (2, 2), (2, 3), -- Admin gets view, add, edit users
(3, 1); -- User gets only view users

-- Seed User Administrator Default
-- Password default adalah: admin123 (di-hash menggunakan BCRYPT)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `name`, `role_id`) VALUES
(1, 'superadmin', '$2y$10$hzqMSXvM8doU0PwxVbIx2eQg57V6O6jBB/9btqFlXomKd9yWYYydC', 'superadmin@framework.ai', 'Super Admin Utama', 1);

-- Seed Settings Aplikasi Global
INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('app_name', 'Framework AI System', 'Nama aplikasi utama yang tampil di interface.'),
('allow_registration', 'false', 'Flag untuk memperbolehkan registrasi mandiri.');

SET FOREIGN_KEY_CHECKS = 1;
