ALTER TABLE `pay_config`
MODIFY COLUMN `key` varchar(1700) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '私钥' AFTER `partner_id`,
MODIFY COLUMN `pub_key` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '公钥' AFTER `key`;

ALTER TABLE `bank`
MODIFY COLUMN `code` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '银行英文简称\r\n@a:r' AFTER `id`,
MODIFY COLUMN `shortname` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行简称\r\n' AFTER `name`;

ALTER TABLE `profile`
MODIFY COLUMN `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '用户真实姓名' AFTER `nickname`;

ALTER TABLE `transfer_log`
MODIFY COLUMN `response` varchar(2000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `json`;