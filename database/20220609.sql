#BNG分类
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `status`, `switch`,`across_status`, `sort`) VALUES 
(99, 4, 'BNG', 'BNG', 'BNG', 'BNG', 'enabled','enabled','enabled', '16');

#BNG游戏
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `alias`, `rename`, `game_img`) VALUES 
(5224, '139', 99, 'Book of Sun', 'bookofsun', '法老宝典', 'https://update.lodigame.com/lodi/game/vert/bng/bookofsun.png'),

#BNG注单
DROP TABLE IF EXISTS `game_order_bng`;
CREATE TABLE `game_order_bng`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'user_id',
  `round` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '交易流水号',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家账户',
  `gameDate` datetime NULL DEFAULT NULL COMMENT '游戏时间',
  `gameCode` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏代码',
  `betAmount` int(11) NULL DEFAULT NULL COMMENT '下注金额',
  `winAmount` int(11) NULL DEFAULT NULL COMMENT '赢得金额',
  `income` int(11) NULL DEFAULT NULL COMMENT '赢得金额-下注金额',
  `startTime` datetime NULL DEFAULT NULL COMMENT '开始时间',
  `status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '交易状态(\"OK\", \"NEW\", \"LOCKED\", \"EXCEED\")',
  `gameplat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲平台',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `OCode`(`round`) USING BTREE,
  INDEX `index_gameDate`(`gameDate`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'BNG' ROW_FORMAT = DYNAMIC;

#BNG打码量
ALTER TABLE `user_dml` ADD COLUMN `BNG` int(10) UNSIGNED NULL DEFAULT 0 AFTER `updated`;