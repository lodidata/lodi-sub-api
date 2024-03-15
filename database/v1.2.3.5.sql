#转盘活动
alter table active_apply add `matter_name` varchar(200) NOT NULL DEFAULT '' COMMENT '活动名称' after withdraw_require;
alter table active_apply add `award_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '中奖类型 1彩金 2谢谢参与 3物品' after active_name;

CREATE TABLE `active_times` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL COMMENT 'user表中的id,用户ID',
    `times` int(11) NOT NULL DEFAULT '0' COMMENT '次数',
    `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '钥匙类型 1青铜 2黄金 3白金',
    `end_time` timestamp NULL DEFAULT NULL COMMENT '到期时间',
    `sent_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '1、统一派送，2、分享派送',
    `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_time` (`user_id`,`end_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='转盘次数流水表';

#彩金活动新增条件
ALTER TABLE `active_handsel`
ADD COLUMN `recharge_limit` tinyint NOT NULL DEFAULT 0 COMMENT '是否需要充值才能领取彩金：0-否，1-是' AFTER `receive_way`,
ADD COLUMN `recharge_type` tinyint NOT NULL DEFAULT 0 COMMENT '充值类型：1-单笔，2-累计' AFTER `recharge_limit`,
ADD COLUMN `recharge_coin` int NOT NULL DEFAULT 0 COMMENT '充值金额' AFTER `recharge_type`;

#充值回调时间限制
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('recharge', '充值回调时间限制', 'int', 'rechargeCallbackTime', '0', '多少分钟内允许充值回调', 'enabled', NULL);
#添加权限
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (378, 43, '账号停用启用', 'PUT', '/user/state', NULL, 1);
