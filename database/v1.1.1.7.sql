#充值类活动
ALTER TABLE `active_rule`
ADD COLUMN `give_condition` int(11) NULL COMMENT '赠送条件,1:单日首笔,2:单日累计,3:周累计,4:月累计,5:自定义' AFTER `give_time`,
ADD COLUMN `give_date` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '自定义时间' AFTER `give_condition`;

#充值表活动id
ALTER TABLE `funds_deposit`
MODIFY COLUMN `active_apply` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '参与活动' AFTER `coupon_withdraw_bet`;



#活动分类
INSERT INTO `active_type`(`id`, `name`, `description`, `sort`, `image`, `status`, `created_uid`, `updated_uid`)
VALUES (11, '充值分类', '充值分类', 11, '', 'enabled', NULL, NULL);

#活动模板
INSERT INTO `active_template`(`id`, `name`, `description`, `state`, `created_uid`)
VALUES (11, 'Recharge Classification', 'Recharge Classification', '', NULL);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (268, 146, '充值留存率报表', NULL, '', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (269, 146, '活跃留存率报表', NULL, '', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (270, 268, '信息查询', 'GET', '/report/retention/deposit', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (271, 268, '导出', 'GET', '/report/retention/deposit/export', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (272, 269, '信息查询', 'GET', '/report/retention/active', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (273, 269, '导出', 'GET', '/report/retention/active/export', NULL, 1);

#后台首页统计第二部分
CREATE TABLE `admin_index_second`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `online` int(11) NOT NULL COMMENT '在线人数',
  `game` int(11) NOT NULL COMMENT '在玩人数',
  `recharge` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `minute` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '分钟',
  `withdraw` decimal(10, 2) NOT NULL COMMENT '兑换金额',
  `hour` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '小时',
  `day` date NOT NULL COMMENT '天',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `day`(`day`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '后台首页时实数据统计' ROW_FORMAT = Dynamic;

#后台首页统计第三四部分
CREATE TABLE `admin_index_third`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `register_new` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新注册',
  `game_user_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT ' 活跃用户数',
  `recharge_total` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '总充值',
  `witchdraw_total` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '总兑换',
  `recharge_witchdraw` decimal(10, 2) NOT NULL COMMENT '充兑差',
  `dml_total` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总打码量',
  `inversion_rate` decimal(10, 2) NOT NULL COMMENT '转化率',
  `arppu` decimal(10, 2) NOT NULL COMMENT 'ARPPU=总充值/活跃付费用户数',
  `next_day_extant` decimal(10, 2) NOT NULL COMMENT '次日留存',
  `user_agent` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增代理数',
  `agent_new_user_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新代理新首充会员数',
  `recharge_first_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '首充会员数',
  `recharge_first_money` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '首充总金额',
  `recharge_first_avg` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '首充会员平均金额',
  `day` date NULL DEFAULT NULL COMMENT '统计日期',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `day`(`day`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '后台首页统计第三四部分' ROW_FORMAT = Dynamic;