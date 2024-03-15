#活动
ALTER TABLE `active_rule`
ADD COLUMN `game_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏分类' AFTER `give_date`,
ADD COLUMN `limit_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '解除限制值' AFTER `game_type`;


CREATE TABLE `active_register` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL COMMENT '用户id',
`active_id` int(11) NOT NULL COMMENT '活动id',
`amount` int(11) NOT NULL DEFAULT '0' COMMENT '发放金额',
`game_type` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '游戏分类',
`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态,1:未满足,2:已满足',
PRIMARY KEY (`id`),
KEY `IDX_USER_ID` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='注册彩金限制';