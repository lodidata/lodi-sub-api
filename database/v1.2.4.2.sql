#支付渠道
ALTER TABLE `pay_channel`
    ADD COLUMN `currency_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '货币类型,1:法定货币,2:数字货币' AFTER `name`;

#支付通道
ALTER TABLE `payment_channel`
    ADD COLUMN `currency_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '货币类型,1:法定货币,2:数字货币' AFTER `name`,
ADD COLUMN `coin_type` set('ERC20','TRC20','Omni') NOT NULL DEFAULT '' COMMENT '链类型' AFTER `currency_type`,
ADD COLUMN `currency_id` tinyint(11) NOT NULL DEFAULT '0' COMMENT '货币汇率表id' AFTER `coin_type`;


#充值
ALTER TABLE `funds_deposit`
    ADD COLUMN `currency_name` varchar(50) NOT NULL DEFAULT '' COMMENT '货币名称' AFTER `passageway_active`,
ADD COLUMN `coin_type` enum('ERC20','TRC20','Omni') NOT NULL DEFAULT '' COMMENT '链类型' AFTER `currency_name`,
ADD COLUMN `currency_amount` double NOT NULL DEFAULT '0' COMMENT '货币金额' AFTER `coin_type`,
ADD COLUMN `rate` double  NOT NULL DEFAULT '0' COMMENT '汇率' AFTER `currency_amount`;


#初始化数据
update payment_channel set currency_id=1;



#u2pay 备注 必须先执行上面SQL 再执行这下面的SQL
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('U2PAY', 'u2pay', '121038', 'ssW2I1pNqU_68ojxOPnzQ', '8gVenjULQU_327KO5zFd8w', 'https://usdt.tgpas.com', 0, 0, '20.189.72.226', 'h5', '2022-10-08 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','{\"USDT\":{\"Omni\":10,\"ERC20\":11,\"TRC20\":12}}');
INSERT INTO pay_channel(`id`,`name`,`currency_type`,`type`) values (13,'USDT',2,'USDT');
INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`,`name`,`status`,`currency_type`,`coin_type`,`currency_id`) select '13' as pay_channel_id, id as pay_config_id, 'U2PAY' as name,0 as status,2 as currency_type,'' as coin_type,4 as currency_id from pay_config where type='u2pay';
insert into level_payment (level_id,payment_id) SELECT t1.id AS level_id,t2.id AS payment_id FROM user_level t1 JOIN payment_channel t2 where t2.name='U2PAY';

#ufuc2cpay支付
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('UFUC2CPAY', 'ufuc2cpay', '0e43bc2cac1f6294', 'b715817cbe67930c35e539f4dbe1f47b', 'b715817cbe67930c35e539f4dbe1f47b', 'https://mapi.ufuc2c.com', 0, 0, '', 'h5', '2022-10-08 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','{\"USDT\":{\"ERC20\":\"ERC20-USDT\",\"TRC20\":\"TRC20-USDT\"}}');


INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`,`name`,`status`,`currency_type`,`coin_type`,`currency_id`) select '13' as pay_channel_id, id as pay_config_id, 'UFUC2CPAY' as name,0 as status,2 as currency_type,'' as coin_type,4 as currency_id from pay_config where type='ufuc2cpay';
insert into level_payment (level_id,payment_id) SELECT t1.id AS level_id,t2.id AS payment_id FROM user_level t1 JOIN payment_channel t2 where t2.name='UFUC2CPAY';

#系统配置
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('system', '平台使用货币', 'int', 'currency_id', '1', '货币id', 'enabled', NULL);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (388, 73, '货币汇率', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (389, 388, '汇率修改', 'PUT', '/cash/exchangerate', NULL, 1);

CREATE TABLE `currency_exchange_rate` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `type` int(11) NOT NULL DEFAULT '0' COMMENT '货币类型(1.法定货币 2.数字货币)',
      `name` varchar(100) NOT NULL DEFAULT '' COMMENT '货币简称',
      `alias` varchar(100) NOT NULL DEFAULT '' COMMENT '简称',
      `exchange_rate` double NOT NULL DEFAULT '0.00' COMMENT '汇率',
      `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='货币汇率';

INSERT INTO currency_exchange_rate(`type`,`name`,`alias`,`exchange_rate`) values(1,'菲律宾比索','PHP',1),(1,'泰铢','THB',0.62),(1,'墨西哥比索','MXN',0.34),(2,'泰达币','USDT',0.018);