
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for rebet_exec
-- ----------------------------
DROP TABLE IF EXISTS `rebet_exec`;
CREATE TABLE `rebet_exec`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL DEFAULT '0000-00-00' COMMENT '返水日期',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型（1：游戏，2：彩票）',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `udx_date_type`(`date`, `type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '返水执行记录表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
