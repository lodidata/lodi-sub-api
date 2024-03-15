
#股东分红
ALTER TABLE `funds`
    ADD COLUMN `share_balance` int(11) NOT NULL DEFAULT 0 COMMENT '股东分红余额' AFTER `freeze_password`,
    ADD COLUMN `share_freeze_withdraw` int(11) NOT NULL DEFAULT 0 COMMENT '股东分红提现冻结金额' AFTER `share_balance`;


#提现记录
ALTER TABLE `funds_withdraw`
    ADD COLUMN `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '提现类型,1:主钱包提现,2:股东分红提现' AFTER `user_type`;


#活动
ALTER TABLE `active_rule`
    ADD COLUMN `game_type` varchar(100)  NULL DEFAULT NULL COMMENT '游戏分类' AFTER `give_date`,
    ADD COLUMN `limit_value` varchar(100)  NULL DEFAULT NULL COMMENT '解除限制值' AFTER `game_type`;

#注册彩金限制
CREATE TABLE `active_register` (
                                   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                   `user_id` int(11) NOT NULL COMMENT '用户id',
                                   `active_id` int(11) NOT NULL COMMENT '活动id',
                                   `amount` int(11) NOT NULL DEFAULT '0' COMMENT '发放金额',
                                   `game_type` varchar(50)  NOT NULL DEFAULT '' COMMENT '游戏分类',
                                   `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                                   `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态,1:未满足,2:已满足',
                                   PRIMARY KEY (`id`),
                                   KEY `IDX_USER_ID` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='注册彩金限制';