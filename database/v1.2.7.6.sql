#用户周薪表
CREATE TABLE `user_week_award` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
       `award_date` date NOT NULL COMMENT '统计日期',
       `user_id` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '用户ID',
       `level` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户等级',
       `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名称',
       `bet_money` int(11) NOT NULL DEFAULT '0' COMMENT '周投注金额，以分为单位',
       `award_money` int(11) NOT NULL DEFAULT '0' COMMENT '周俸禄金额，以分为单位',
       `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
       `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
       `status` tinyint(4) DEFAULT NULL COMMENT '状态 1:未发放,2:未领取,3:已领取',
       `batch_no` int(11) DEFAULT NULL COMMENT '批次号',
       `dml_amount` int(11) DEFAULT '0' COMMENT '打码量',
       `process_time` timestamp NULL DEFAULT NULL COMMENT '领取时间',
       PRIMARY KEY (`id`),
       UNIQUE KEY `uniq_user_level` (`user_id`,`award_date`)
) ENGINE=InnoDB COMMENT='用户周薪表';

CREATE TABLE `agent_bkge_log` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `agent_id` int(10) NOT NULL COMMENT '代理id',
      `agent_name` varchar(255) NOT NULL COMMENT '代理名称',
      `user_id` int(10) unsigned NOT NULL COMMENT '直属用户id',
      `user_name` varchar(255) NOT NULL COMMENT '直属用户账号',
      `bkge_money` int(10) NOT NULL COMMENT '返佣金额（分）',
      `game_type` varchar(50) NOT NULL COMMENT '游戏类型',
      `date` date NOT NULL COMMENT '日期',
      `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
      PRIMARY KEY (`id`) USING BTREE,
      UNIQUE KEY `idx_union` (`agent_id`,`user_id`,`game_type`,`date`) USING BTREE,
      KEY `idx_game_type` (`game_type`) USING BTREE,
      KEY `idx_date` (`date`) USING BTREE,
      KEY `idx_agent_id` (`agent_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏返佣记录';

#用户晋升彩金
CREATE TABLE `user_level_winnings` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
   `award_date` date NOT NULL COMMENT '统计日期',
   `user_id` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '用户ID',
   `level` int(11) NOT NULL COMMENT '等级',
   `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名称',
   `money` int(11) NOT NULL DEFAULT '0' COMMENT '晋升彩金',
   `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
   `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
   `status` tinyint(4) DEFAULT '0' COMMENT '状态2未领取 3已领取',
   `dml_amount` int(11) DEFAULT '0' COMMENT '打码量',
   `process_time` timestamp NULL DEFAULT NULL COMMENT '领取时间',
   PRIMARY KEY (`id`),
   KEY `idx_user_level` (`user_id`,`level`) USING BTREE
) ENGINE=InnoDB  COMMENT='用户晋升彩金记录表';

#VIP页面
ALTER TABLE `user_level` ADD COLUMN `background` varchar(255) NOT NULL DEFAULT '' COMMENT '奖励背景图片'
ALTER TABLE `user_level` ADD COLUMN `level_background` varchar(255) NOT NULL DEFAULT '' COMMENT '等级背景图片'
ALTER TABLE `user_level` ADD COLUMN `week_money` int(11) NOT NULL DEFAULT '0' COMMENT '周薪' AFTER `monthly_recharge`;
ALTER TABLE `user_level` ADD COLUMN `week_recharge` int(11) NOT NULL DEFAULT '0' COMMENT '周薪充值条件，分为单位' AFTER `week_money`;
ALTER TABLE `user_level` ADD COLUMN `welfare` varchar(255) NOT NULL DEFAULT '' COMMENT '福利特权 ,withdraw_day_times(每日取款次数),withdraw_min,withdraw_max(G卡和maya单次取款额),bank_withdraw_min,bank_withdraw_max(银行卡单次取款额),daily_withdraw_max(每日取款总额度)' AFTER `week_recharge`;
ALTER TABLE `user_level` ADD COLUMN `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提款手续费' AFTER `welfare`;
#VIP页面 周薪月薪领取
ALTER TABLE `user_data` ADD COLUMN `week_award_id` int(11) NOT NULL DEFAULT '0' COMMENT '周薪'
ALTER TABLE `user_data` ADD COLUMN `monthly_award_id` int(11) NOT NULL DEFAULT '0' COMMENT '月薪'

ALTER TABLE `user_monthly_award` ADD COLUMN `level` int(11) NOT NULL DEFAULT '0' COMMENT '用户等级'
#等级福利特权初始值
UPDATE user_level set welfare  = '{"withdraw_day_times":0,"withdraw_min":0,"withdraw_max":0,"bank_withdraw_min":0,"bank_withdraw_max":0,"daily_withdraw_max":0}'
UPDATE user_level set icon  = '/vip/0.png',level_background = '/vip/VIP0.png',background = '/vip/rule0.png' where id = 1;
UPDATE user_level set icon  = '/vip/1.png',level_background = '/vip/VIP1.png',background = '/vip/rule1.png' where id = 2;
UPDATE user_level set icon  = '/vip/2.png',level_background = '/vip/VIP2.png',background = '/vip/rule2.png' where id = 3;
UPDATE user_level set icon  = '/vip/3.png',level_background = '/vip/VIP3.png',background = '/vip/rule3.png' where id = 4;
UPDATE user_level set icon  = '/vip/4.png',level_background = '/vip/VIP4.png',background = '/vip/rule4.png' where id = 5;
UPDATE user_level set icon  = '/vip/5.png',level_background = '/vip/VIP5.png',background = '/vip/rule5.png' where id = 6;
UPDATE user_level set icon  = '/vip/6.png',level_background = '/vip/VIP6.png',background = '/vip/rule6.png' where id = 7;
UPDATE user_level set icon  = '/vip/7.png',level_background = '/vip/VIP7.png',background = '/vip/rule7.png' where id = 8;
UPDATE user_level set icon  = '/vip/8.png',level_background = '/vip/VIP8.png',background = '/vip/rule8.png' where id = 9;
UPDATE user_level set icon  = '/vip/9.png',level_background = '/vip/VIP9.png',background = '/vip/rule9.png' where id = 10;
UPDATE user_level set icon  = '/vip/10.png',level_background = '/vip/VIP10.png',background = '/vip/rule10.png' where id = 11;
UPDATE user_level set icon  = '/vip/11.png',level_background = '/vip/VIP11.png',background = '/vip/rule11.png' where id = 12;
UPDATE user_level set icon  = '/vip/12.png',level_background = '/vip/VIP12.png',background = '/vip/rule12.png' where id = 13;

# 奖励颜色&分割线
ALTER TABLE `user_level` ADD COLUMN `split_line` varchar(255) NULL DEFAULT '' COMMENT '分割线图片' AFTER `level_background`,
ALTER TABLE `user_level` ADD COLUMN `font_color` varchar(50) NULL DEFAULT '' COMMENT '奖励文字颜色' AFTER `split_line`;