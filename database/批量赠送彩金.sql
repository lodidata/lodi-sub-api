INSERT INTO `active_type` (`id`, `name`, `description`, `sort`, `created`, `updated`) VALUES (12, '批量赠送彩金', '批量赠送彩金', 12, '2022-07-15 17:10:25', '2022-07-15 17:10:29');
INSERT INTO `active_template` (`id`, `name`, `description`, `state`, `created_uid`, `created`, `updated`) VALUES (12, '批量赠送彩金', '批量赠送彩金', '', NULL, '2022-07-15 17:18:16', '2022-07-15 17:18:19');

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (297, 146, '批量赠送彩金', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (298, 297, '批量赠送彩金查询', 'GET', '/report/handsel', NULL, 1);

#彩金活动表
CREATE TABLE `active_handsel`  (
       `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
       `active_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动id，对应active表的id',
       `msg_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息标题',
       `msg_content` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息内容',
       `give_away` tinyint(4) NOT NULL DEFAULT 0 COMMENT '赠送条件：0-未知不赠送，1-指定用户，2-指定等级，3-批量赠送',
       `phone_list` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '指定用户列表，多个手机号之间英文逗号隔开',
       `user_level` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '指定等级列表，多个等级之间英文逗号隔开',
       `batch` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '批量赠送列表，多个手机号之间英文逗号隔开',
       `batch_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '批量赠送时，上传的文件地址',
       `give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '赠送彩金数量',
       `dm_num` int(11) NOT NULL DEFAULT 0 COMMENT '打码量',
       `notice_type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '通知类型：1-短信，2-邮箱，3站内信息通知，多种方式时编号之间用逗号隔开',
       `give_amount_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '赠送彩金时间',
       `is_now_give` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否立即赠送彩金：1-是，0-否',
       `state` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否已经赠送彩金：1-是，0-否',
       `create_time` datetime NOT NULL DEFAULT '1997-01-01 00:00:00' COMMENT '创建时间',
       PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '批量赠送彩金活动内容' ROW_FORMAT = Dynamic;

#彩金发放记录
CREATE TABLE `active_handsel_log`  (
       `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
       `active_handsel_id` int(11) NOT NULL DEFAULT 0 COMMENT '批量赠送彩金活动规则的id',
       `msg_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息标题',
       `msg_content` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息内容',
       `give_away` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '赠送方式',
       `notice_away` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '通知方式',
       `give_num` int(11) NOT NULL DEFAULT 0 COMMENT '赠送人数',
       `give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '赠送的彩金数',
       `dm_num` int(11) NOT NULL DEFAULT 0 COMMENT '打码量',
       `total_give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '总赠送彩金数量',
       `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
       `give_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '赠送时间',
       PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '批量赠送彩金发放记录' ROW_FORMAT = Dynamic;