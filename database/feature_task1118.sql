ALTER TABLE `active_handsel`
    ADD COLUMN `admin_id` int(10) UNSIGNED NULL AFTER `status`,
    ADD COLUMN `admin_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `admin_id`;