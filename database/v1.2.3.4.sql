#意见与反馈
CREATE TABLE `user_feedback` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
     `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名称',
     `mobile` varchar(50) DEFAULT '' COMMENT '手机号',
     `type` tinyint(4) DEFAULT '0' COMMENT '反馈类型 1、建议反馈 2、有奖举报 3、BUG反馈 4、其他',
     `question` text COMMENT '问题描述',
     `img` text COMMENT '截图图片',
     `origin` tinyint(4) DEFAULT '0' COMMENT '来源，1:pc, 2:h5, 3:ios,4:android',
     `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 0待处理 1已处理',
     `reply` text COMMENT '回复内容',
     `reply_time` timestamp NULL DEFAULT NULL COMMENT '回复时间',
     `operate_uid` int(11) DEFAULT NULL COMMENT '操作者',
     `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
     `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`) USING BTREE,
     KEY `created` (`created`) USING BTREE
) ENGINE=InnoDB CHARSET=utf8mb4 COMMENT='意见与反馈表';

insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(371,123,'意见与反馈',null,null,null,1),(372,371,'查询','GET','user/feedback',1,1),(373,371,'编辑','PUT','user/feedback',2,1);

#添加首页权限
INSERT INTO `lodi_game`.`admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (370, 0, '首页', 'GET', '/index/second', NULL, 1);

#彩金活动
ALTER TABLE `active_handsel` ADD COLUMN `unfixed_url` varchar(255) NOT NULL DEFAULT '' COMMENT '非固定方式赠送文件url' AFTER `give_away`;
ALTER TABLE `active_handsel` ADD COLUMN `receive_way` tinyint NOT NULL DEFAULT 1 COMMENT '赠送彩金方式：1-手动领取，2-直接发送至主钱包' AFTER `valid_time`;
ALTER TABLE `active_handsel` MODIFY COLUMN `msg_content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '消息内容' AFTER `msg_title`;

#彩金活动新增条件
ALTER TABLE `active_handsel`
ADD COLUMN `recharge_limit` tinyint NOT NULL DEFAULT 0 COMMENT '是否需要充值才能领取彩金：0-否，1-是' AFTER `receive_way`,
ADD COLUMN `recharge_type` tinyint NOT NULL DEFAULT 0 COMMENT '充值类型：1-单笔，2-累计' AFTER `recharge_limit`,
ADD COLUMN `recharge_coin` int NOT NULL DEFAULT 0 COMMENT '充值金额' AFTER `recharge_type`;
