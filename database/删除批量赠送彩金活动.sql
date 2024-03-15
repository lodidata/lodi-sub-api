ALTER TABLE `active_handsel`
ADD COLUMN `status` tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '状态 1：正常，0：已删除' AFTER `uid_list`;