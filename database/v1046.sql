ALTER TABLE `active_apply`
ADD COLUMN `batch_no` int(11) NULL COMMENT ''批次号'' AFTER `apply_count_status`,
ADD INDEX `idx_batch_no`(`batch_no`) USING BTREE;



CREATE TABLE `active_backwater` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`active_id` int(11) DEFAULT NULL COMMENT '活动id',
`batch_no` int(11) NOT NULL COMMENT '批次号',
`type` tinyint(3) DEFAULT NULL COMMENT '类型,1:日返水,2:周返水,3:月返水',
`batch_time` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '返水批次',
`back_cnt` int(11) DEFAULT '0' COMMENT '返水人数',
`receive_cnt` int(11) DEFAULT '0' COMMENT '已领取人数',
`back_amount` int(11) DEFAULT '0' COMMENT '返水金额',
`receive_amount` int(11) DEFAULT '0' COMMENT '已领取金额',
`status` tinyint(3) DEFAULT '0' COMMENT '状态,0:待返水,1:返水中,2:已返水',
`send_time` timestamp NULL DEFAULT NULL COMMENT '赠送时间',
`create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `IDX_BATCH_NO` (`batch_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='返水审核';



INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (488, 73, '返水审核', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (489, 488, '返水审核查询', 'GET', '/cash/backwater', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (490, 488, '返水审核详情', 'GET', '/cash/backwater/detail', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (491, 488, '返水审核发放', 'PATCH', '/cash/backwater', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (492, 488, '返水审核详情导出', 'GET', '/cash/backwater/detail/export', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (493, 488, '返水审核导出', 'GET', '/cash/backwater/export', NULL, 1);

