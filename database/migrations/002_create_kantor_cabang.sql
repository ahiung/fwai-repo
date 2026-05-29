/**
 * Tanggal Pembuatan: 2026-05-29
 * Nama Pembuat: Antigravity - AI Software Engineer & Security Expert
 * File: 002_create_kantor_cabang.sql
 * Deskripsi: Membuat tabel kantor_cabang dan menyisipkan hak akses (permissions) granular
 *            baru untuk modul kantor cabang serta memetakan hak akses ke peran Superadmin (ID 1).
 */

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. TABEL KANTOR_CABANG
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `kantor_cabang`;
CREATE TABLE `kantor_cabang` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`office_code` VARCHAR(50) NOT NULL COMMENT 'Kode unik kantor (mis: KTR-001, BR-01)' COLLATE 'utf8mb4_unicode_ci',
	`name` VARCHAR(150) NOT NULL COMMENT 'Nama kantor/cabang' COLLATE 'utf8mb4_unicode_ci',
	`office_type` VARCHAR(50) NOT NULL DEFAULT 'branch' COMMENT 'Tipe kantor (branch/head/remote)' COLLATE 'utf8mb4_unicode_ci',
	`address` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`city` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`province` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`postal_code` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`country` VARCHAR(100) NULL DEFAULT 'Indonesia' COLLATE 'utf8mb4_unicode_ci',
	`phone` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`email` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`latitude` DECIMAL(10,8) NULL DEFAULT NULL,
	`longitude` DECIMAL(11,8) NULL DEFAULT NULL,
	`manager_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'Referensi ke users.id (manager/kepala kantor)',
	`status` ENUM('active','inactive') NOT NULL DEFAULT 'active' COLLATE 'utf8mb4_unicode_ci',
	`created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `office_code` (`office_code`) USING BTREE,
	INDEX `fk_kantor_manager_user` (`manager_id`) USING BTREE,
	INDEX `fk_kantor_created_by_user` (`created_by`) USING BTREE,
	INDEX `fk_kantor_updated_by_user` (`updated_by`) USING BTREE,
	CONSTRAINT `fk_kantor_created_by_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE RESTRICT ON DELETE SET NULL,
	CONSTRAINT `fk_kantor_manager_user` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON UPDATE RESTRICT ON DELETE SET NULL,
	CONSTRAINT `fk_kantor_updated_by_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE RESTRICT ON DELETE SET NULL
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=2
;

-- ------------------------------------------------------------------------------
-- 2. MENAMBAH HAK AKSES BARU UNTUK KANTOR CABANG
-- ------------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(10, 'view_kantor_cabang', 'Melihat daftar dan detail data kantor cabang.'),
(11, 'add_kantor_cabang', 'Menambah data kantor cabang baru.'),
(12, 'edit_kantor_cabang', 'Mengubah data kantor cabang.'),
(13, 'delete_kantor_cabang', 'Menghapus data kantor cabang (soft delete).');

-- ------------------------------------------------------------------------------
-- 3. MENGHUBUNGKAN HAK AKSES KE PERAN SUPERADMIN (ROLE ID 1)
-- ------------------------------------------------------------------------------
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 10),
(1, 11),
(1, 12),
(1, 13);

SET FOREIGN_KEY_CHECKS = 1;
