ALTER TABLE `funds`
ADD COLUMN `direct_balance` int(10) NOT NULL DEFAULT 0 COMMENT '直推余额' AFTER `share_freeze_withdraw`;

INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推活动开关', 'bool', 'direct_switch', '0', '1开，0关', 'enabled', '2023-02-09 15:09:41');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推赠送打码量', 'int', 'send_dml', '0', NULL, 'enabled', '2023-02-09 15:09:35');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推现金奖励-推广注册赠送', 'json', 'cash_promotion_register', '{\"send_amount\":0,\"get_limit\":0}', 'send_amount赠送金额，get_limit获取上限', 'enabled', '2023-02-09 18:29:49');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推现金奖励-推广充值赠送', 'json', 'cash_promotion_recharge', '{\"recharge_amount\":0,\"send_amount\":0,\"get_limit\":0}', 'recharge_amount充值金额，send_amount赠送金额，get_limit获取上限', 'enabled', '2023-02-09 18:29:26');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推现金奖励-被推广注册赠送', 'json', 'cash_be_pushed_register', '{\"send_amount\":0,\"get_limit\":0}', 'send_amount赠送金额，get_limit获取上限', 'enabled', '2023-02-09 18:29:51');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推现金奖励-被推广充值赠送', 'json', 'cash_be_pushed_recharge', '{\"recharge_amount\":0,\"send_amount\":0,\"get_limit\":0}', 'recharge_amount充值金额，send_amount赠送金额，get_limit获取上限', 'enabled', '2023-02-09 18:29:42');



CREATE TABLE `direct_bkge`  (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `serial_no` int(11) NOT NULL COMMENT '编号',
                                `register_count` int(11) NOT NULL COMMENT '注册人数',
                                `recharge_count` int(11) NOT NULL COMMENT '充值人数',
                              `bkge_increase` decimal(18, 2) NOT NULL COMMENT '返水提升比例',
                                PRIMARY KEY (`id`) USING BTREE,
                                INDEX `serial_no`(`serial_no`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '直推返水配置' ROW_FORMAT = Dynamic;

CREATE TABLE `direct_imgs`  (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `type` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '类型',
                                `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称',
                                `desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '说明',
                                `img` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片',
                                `reward` int(11) NULL DEFAULT 0 COMMENT '绑定奖励 只有未绑定有',
                                PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '直推图片配置' ROW_FORMAT = Dynamic;
INSERT INTO `direct_imgs` VALUES (1, 'promotion_rule', '推广规则', '123', '3123', NULL);
INSERT INTO `direct_imgs` VALUES (2, 'bkge_rule', '返水规则', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (3, 'user_promotion', '用户推广使用的图片', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (4, 'be_pushed', '被推广弹窗', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (5, 'register_finish', '完成注册弹窗', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (6, 'index_promotion', '首页推广成功弹窗', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (7, 'no_bind', '直推界面-未绑定banner', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (8, 'no_recharge', '直推界面-未充值banner', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (9, 'normal', '直推界面-普通banner1', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (10, 'normal', '直推界面-普通banner2', '', '', NULL);
INSERT INTO `direct_imgs` VALUES (11, 'normal', '直推界面-普通banner3', '', '', NULL);

CREATE TABLE `direct_record`  (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
      `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户uid',
      `username` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户名称',
      `sup_uid` int(10) UNSIGNED NOT NULL COMMENT '上级用户uid',
      `sup_name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '上级用户名称',
      `type` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类别：1注册，2充值',
      `price` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励金额（分）',
      `dml` int(11) UNSIGNED NULL DEFAULT 0 COMMENT '打码量',
      `is_transfer` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已转出主钱包（0：否，1：是）',
      `is_read` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已读（0：否，1：是）',
      `date` date NOT NULL COMMENT '创建日期',
      `created` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) COMMENT '创建时间',
      `updated` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
      PRIMARY KEY (`id`) USING BTREE,
      INDEX `user_id`(`user_id`) USING BTREE,
      INDEX `username`(`username`) USING BTREE,
      INDEX `idx_sup_uid`(`sup_uid`) USING BTREE,
      INDEX `idx_date`(`date`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '直推推广记录' ROW_FORMAT = Dynamic;

insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(438,73,'推广记录',null,null,null,1),(439,438,'推广记录查询','GET','/direct/record',null,1);
#系统设置 增加直推金额 额度审核
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '增加直推奖励', 'int', 'add_direct_balance', 0, '', 'enabled');

#user_data增加字段
ALTER TABLE `user_data`
ADD COLUMN `direct_deposit` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '直推总推广充值人数' AFTER `created`,
ADD COLUMN `direct_register` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '直推总推广注册人数' AFTER `direct_deposit`,
ADD COLUMN `direct_award` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '直推已获得总奖励数' AFTER `direct_register`,
ADD COLUMN `direct_reg_award` int NOT NULL DEFAULT 0 COMMENT '直推注册获得奖励' AFTER `direct_award`,
ADD COLUMN `direct_recharge_award` int NOT NULL DEFAULT 0 COMMENT '直推充值获得奖励' AFTER `direct_reg_award`;

INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (446, 73, '直推奖励配置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (447, 446, '直推奖励配置查询', 'GET', '/direct/config', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (448, 446, '直推基本配置修改', 'PUT', '/direct/config', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (449, 446, '直推返水添加', 'POST', '/direct/bkge', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (450, 446, '直推返水修改', 'PUT', '/direct/bkge', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (451, 446, '直推返水删除', 'DELETE', '/direct/bkge', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (452, 446, '直推图片编辑', 'PUT', '/direct/image', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (453, 73, '直推统计', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (454, 453, '直推统计查询', 'GET', '/direct/statistics', NULL, 1);

INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('direct', '直推文本', 'string', 'direct_content', '点击下面链接立即获得奖励 %s', '推广文本', 'enabled', '2023-04-12 14:20:15');

ALTER TABLE `direct_imgs` ADD COLUMN `status` enum('enabled','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'enabled' COMMENT '状态（enabled启用 disabled停用）' AFTER `reward`;
ALTER TABLE `user_agent` ADD COLUMN `direct_reg` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '是否为直推开启后注册，1：是，0：否' AFTER `default_profit_loss_value`;