#安装包表
CREATE TABLE `app_package` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '安装包类型 1安卓 2苹果',
   `name` varchar(120) NOT NULL DEFAULT '' COMMENT '安装包名称',
   `version` varchar(120) NOT NULL DEFAULT '' COMMENT '版本',
   `bag_url` varchar(200) NOT NULL DEFAULT '' COMMENT '安装包地址',
   `icon_url` varchar(200) NOT NULL DEFAULT '' COMMENT '图标地址',
   `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 0正常，1停用',
   `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
   `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='安装包管理\r\n@author: stanley';