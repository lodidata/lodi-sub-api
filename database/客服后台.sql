#客服管理
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(315,0,'客服管理',null,null,null,1),(316,315,'电访后台',null,null,null,1),(317,316,'查询','GET','/kefu/telecom',1,1),(318,316,'编辑客服','POST','/kefu/telecom/export',2,1);

CREATE TABLE `kefu_telecom` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `kefu_id` int(11) NOT NULL DEFAULT '0' COMMENT '客服ID',
    `name` varchar(40) NOT NULL DEFAULT '' COMMENT '客服名称',
    `roll_num` int(11) NOT NULL DEFAULT '0' COMMENT '名单总数',
    `recharge_num` int(11) NOT NULL DEFAULT '0' COMMENT '充值人数',
    `recharge_amount` bigint(20) unsigned DEFAULT '0' COMMENT '充值金额',
    `recharge_mean` bigint(20) unsigned DEFAULT '0' COMMENT '平均充值金额',
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_created` (`created`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='客服电访表';


CREATE TABLE `kefu_telecom_item` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `pid` int(11) NOT NULL DEFAULT '0' COMMENT '电访表ID',
     `user_id` int(11) NOT NULL COMMENT 'user表中的id,用户ID',
     `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号',
     `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
     `register_time` timestamp NULL DEFAULT NULL COMMENT '注册时间',
     `recharge_time` timestamp NULL DEFAULT NULL COMMENT '首充时间',
     `recharge_amount` bigint(20) unsigned DEFAULT '0' COMMENT '首充金额',
     `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
     `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`) USING BTREE,
     UNIQUE KEY `idx_pid_mobile` (`pid`,`mobile`) USING BTREE,
     KEY `idx_created` (`created`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='客服电访明细表';


CREATE TABLE `kefu_user` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `name` varchar(40) NOT NULL COMMENT '客服名称',
     `put_time` timestamp NULL DEFAULT NULL COMMENT '最近导入时间',
     `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
     `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='客服人员表';