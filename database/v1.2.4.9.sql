INSERT INTO `system_config`(`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (226, 'agent', '代理申请页描述', 'string', 'agent_apply_desc', '', NULL, 'enabled', '2023-02-01 14:18:46');

ALTER TABLE `agent_apply`
    MODIFY COLUMN `contact_type` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '联系类型  1手机 2lien 3邮箱 4其他' AFTER `uid_agent_name`,
    MODIFY COLUMN `contact_value` varchar(255) NULL DEFAULT '' COMMENT '联系内容' AFTER `contact_type`;

DROP TABLE IF EXISTS `agent_apply_question`;
CREATE TABLE `agent_apply_question`  (
     `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `title` varchar(255) NOT NULL COMMENT '标题',
     `type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型（1：单选；2：多选；3：文本描述）',
     `required` tinyint(2) NOT NULL COMMENT '是否必填（1：是；2：否）',
     `option` text NULL COMMENT '选项内容',
     `sort` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '排序',
     `created` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) COMMENT '创建时间',
     `updated` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
     PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `agent_apply_submit`;
CREATE TABLE `agent_apply_submit`  (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`apply_id` int(10) UNSIGNED NOT NULL,
`title` varchar(255) NOT NULL COMMENT '标题',
`type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型（1：单选；2：多选；3：文本描述）',
`required` tinyint(2) UNSIGNED NOT NULL COMMENT '是否必填（1：是；2：否）',
`option` text NULL COMMENT '选项内容',
`selected` text NULL COMMENT '选中的内容',
`sort` int(10) UNSIGNED NOT NULL COMMENT '排序',
`created` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '添加时间',
`updated` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
INDEX `idx_apply_id`(`apply_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1;

alter table user_data add `withdraw_cj_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '彩金出款次数' after withdraw_amount;
alter table user_data add `withdraw_cj_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '彩金出款总额' after withdraw_cj_num;

CREATE TABLE `third_logo` (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '第三方名称',
`logo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '第三方logo',
`sort` int(11) DEFAULT NULL,
`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'inserted_at',
`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'updated_at',
PRIMARY KEY (`id`)
) ENGINE=InnoDB;

#渠道报表统计
CREATE TABLE `rpt_channel`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `count_date` date NULL DEFAULT NULL COMMENT '统计日期',
  `channel_id` varchar(255) NULL DEFAULT NULL COMMENT '渠道id',
  `channel_name` varchar(255) NULL DEFAULT NULL COMMENT '渠道名称',
  `award_money` decimal(18, 2) NULL DEFAULT NULL COMMENT '月俸',
  `click` int(11) NULL DEFAULT NULL COMMENT '新增访问次数',
  `cz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '充值金额',
  `cz_person` int(11) NULL DEFAULT NULL COMMENT '充值人数',
  `qk_person` int(11) NULL DEFAULT NULL COMMENT '取款人数',
  `qk_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '取款金额',
  `tz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '投注金额',
  `pc_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '派彩金额',
  `hd_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '活动彩金',
  `hs_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '回水彩金',
  `js_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '晋升彩金',
  `zk_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '转卡彩金',
  `fyz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '返佣总金额',
  `first_recharge_user` int(11) NULL DEFAULT NULL COMMENT '(新充人数)首次充值人数',
  `first_recharge` decimal(18, 2) NULL DEFAULT NULL COMMENT '(新充充值金额)首次充值金额',
  `first_withdraw` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次取款金额',
  `first_bet` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次投注金额',
  `first_prize` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次派彩金额',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_cc`(`count_date`, `channel_id`) USING BTREE,
  INDEX `idx_channel_id`(`channel_id`) USING BTREE,
  INDEX `idx_channel_name`(`channel_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1;

#渠道报表统计
CREATE TABLE `rpt_channel_total`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_id` varchar(255) NULL DEFAULT NULL COMMENT '渠道id',
  `channel_name` varchar(255) NULL DEFAULT NULL COMMENT '渠道名称',
  `award_money` decimal(18, 2) NULL DEFAULT NULL COMMENT '月俸',
  `click` int(11) NULL DEFAULT NULL COMMENT '新增访问次数',
  `cz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '充值金额',
  `cz_person` int(11) NULL DEFAULT NULL COMMENT '充值人数',
  `qk_person` int(11) NULL DEFAULT NULL COMMENT '取款人数',
  `qk_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '取款金额',
  `tz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '投注金额',
  `pc_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '派彩金额',
  `hd_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '活动彩金',
  `hs_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '回水彩金',
  `js_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '晋升彩金',
  `zk_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '转卡彩金',
  `fyz_amount` decimal(18, 2) NULL DEFAULT NULL COMMENT '返佣总金额',
  `first_recharge_user` int(11) NULL DEFAULT NULL COMMENT '(新充人数)首次充值人数',
  `first_recharge` decimal(18, 2) NULL DEFAULT NULL COMMENT '(新充充值金额)首次充值金额',
  `first_withdraw` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次取款金额',
  `first_bet` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次投注金额',
  `first_prize` decimal(18, 2) NULL DEFAULT NULL COMMENT '首次派彩金额',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_channel_id`(`channel_id`) USING BTREE,
  INDEX `idx_chan_name`(`channel_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1;

ALTER TABLE `label` ADD COLUMN `sum` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数' AFTER `content`;

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (436, 123, '首页渠道商logo配置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (437, 436, '首页渠道商logo查询', 'GET', '/block/home/app/thirdLogo', NULL, 1);

#代理申请模板预置问题
#英语
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Contact Method', 1, 1, '[\"Phone\",\"Line\",\"Email\",\"Ws\"]', 1, '2023-02-11 10:22:14', '2023-02-11 10:22:51');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Enter your contact info', 3, 1, NULL, 1, '2023-02-11 10:23:58', '2023-02-11 10:23:58');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Reason to apply', 3, 1, NULL, 1, '2023-02-11 10:24:44', '2023-02-11 10:24:44');

#泰语
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('ช่องทางในการติดต่อ', 1, 1, '[\"Phone\",\"Line\",\"Email\",\"Ws\"]', 2, '2023-02-11 10:23:00', '2023-02-11 10:23:23');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('ใส่ข้อมูลและรายละเอียดสำหรับการติดต่อ', 3, 1, NULL, 2, '2023-02-11 10:24:12', '2023-02-11 10:24:12');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('เหตุผลที่ต้องการเป็นตัวแทน', 3, 1, NULL, 2, '2023-02-11 10:24:54', '2023-02-11 10:24:54');

#墨西哥
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Metodo de contacto', 1, 1, '[\"Phone\",\"Line\",\"Email\",\"Ws\"]', 3, '2023-02-11 10:23:18', '2023-02-11 10:23:29');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Ingrese su información de contacto', 3, 1, NULL, 3, '2023-02-11 10:24:24', '2023-02-11 10:24:24');
INSERT INTO `agent_apply_question`(`title`, `type`, `required`, `option`, `sort`, `created`, `updated`) VALUES ('Razón para aplicar', 3, 1, NULL, 3, '2023-02-11 10:25:05', '2023-02-11 10:25:05');



#redis

del system.config.global.key