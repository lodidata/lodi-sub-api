ALTER TABLE `user` 
MODIFY COLUMN `last_login` int(11) NOT NULL DEFAULT 0 COMMENT '时间截' AFTER `login_ip`

-- 打码量
ALTER TABLE `profile`
ADD COLUMN `total_bet`  int NULL DEFAULT 0 COMMENT '实际打码量' AFTER `postcode`,
ADD COLUMN `total_require_bet`  int NULL DEFAULT 0 COMMENT '应有打码量' AFTER `total_bet`,
ADD COLUMN `free_money`  int NULL DEFAULT 0 COMMENT '可提余额' AFTER `total_require_bet`;

-- 回水统计需求
ALTER table partner ADD COLUMN
	`type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '第三方游戏类型 101-视讯(直播) 102-电子 103-体育';
ALTER table partner ADD COLUMN
  `3th_name` varchar(16) NOT NULL DEFAULT '';
ALTER table partner ADD COLUMN
  `cname` varchar(32) NOT NULL DEFAULT '' COMMENT '中文名字';

alter table rebet add COLUMN
	`plat_id` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '平台ID,对应partner.ID';

-- INSERT INTO partner (id, type,3th_name, cname)
-- 	VALUES
-- 	(1,102, 'game', '电子'),
-- 	(1,102, 'game', '电子'),
-- 	(1,102, 'game', '电子'),
-- 	(1,102, 'game', '电子')
--   ON DUPLICATE KEY UPDATE
-- 	type = VALUES(type)
-- 	and 3th_name = VALUES(3th_name)
-- 	and cname = VALUES(cname);

REPLACE INTO partner
	(id, type,3th_name, cname)
VALUES
	(1,102, 'game', '电子'),
	(4,101, 'live', '视讯'),
	(13,101, 'live', '视讯'),
	(15,103, 'sport', '体育'),
	(16,102, 'game', '电子'),
	(17,103, 'sport', '体育');