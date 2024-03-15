#JDB棋牌
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`) VALUES (87, 17, 'JDBQP', 'JDBQP', 'JDB', 'JDB棋牌');

#余额转出更新失败表
CREATE TABLE `game_money_error`  (
    `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `wid` int(10) UNSIGNED NULL DEFAULT NULL,
  `balance` int(10) NULL DEFAULT 0,
  `game_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tradeNo` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `msg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `type` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '游戏转出金额成功，更新余额失败' ROW_FORMAT = Dynamic;

ALTER TABLE `game_money_error`
CHANGE COLUMN `type` `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0：未处理，1：已处理（成功），2：处理失败' AFTER `msg`,
ADD COLUMN `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `status`,
MODIFY COLUMN `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`;