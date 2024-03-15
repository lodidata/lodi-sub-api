#AutoTopup支付


#代付
INSERT INTO `transfer_config` (`id`, `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`) VALUES (9, 'AutoTopup', 1000000, 'AUTOTOPUP', NULL, '0', NULL, 6, 'enabled', '20zFNEiqdZLwcAu9Cyoggr5IBFf', '20zFNEiqdZLwcAu9Cyoggr5IBFf', NULL, NULL, '20zFNEiqdZLwcAu9Cyoggr5IBFf', NULL, NULL, 0, 0, 500, NULL, NULL, '{\"KBANK\":\"KBANK\",\"SCB\":\"SCB\",\"KTB\":\"KTB\",\"BAY\":\"BAY\",\"GSB\":\"GSB\",\"UOB\":\"UOB\",\"BBL\":\"BBL\",\"CI\":\"CI\",\"LNH\":\"LNH\",\"BAAC\":\"BAAC\",\"OSK\":\"OSK\",\"KNK\":\"KNK\",\"JPK\":\"JPK\",\"CIMB\":\"CIMB\",\"DOIB\":\"DOIB\",\"TISGO\":\"TISGO\",\"TCREDIT\":\"TCREDIT\",\"BNP\":\"BNP\",\"MSU\":\"MSU\",\"MEGA\":\"MEGA\",\"SNT\":\"SNT\",\"CN\":\"CN\",\"USA\":\"USA\",\"ASL\":\"ASL\",\"IZBS\":\"IZBS\",\"TTB\":\"TTB\"}', 'https://api-payment.socialgame.xyz');



#支付
INSERT INTO `pay_config` (`id`, `name`, `type`, `img`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`) VALUES (5, 'AutoTopup', 'autotopup', NULL, 'TEST', '20zFNEiqdZLwcAu9Cyoggr5IBFf', '20zFNEiqdZLwcAu9Cyoggr5IBFf', 'https://api-payment.socialgame.xyz', 0, 0, NULL, 'h5', '2022-02-22 14:06:34', 'enabled', 0, 0, 0, 0, 5, 'json', NULL, '');


INSERT INTO `funds_channel` (`type_id`, `title`, `desc`) VALUES (5, 'AutoTopup', 'AutoTopup');

INSERT INTO level_online (level_id, pay_plat) VALUES
(1,'autotopup'),
(2,'autotopup'),
(3,'autotopup'),
(4,'autotopup'),
(5,'autotopup'),
(6,'autotopup'),
(7,'autotopup'),
(8,'autotopup');


ALTER TABLE ``pay_request_third_log` 
ADD COLUMN `pay_url` varchar(255) NULL AFTER `id`;

ALTER TABLE `funds_deposit` 
MODIFY COLUMN `memo` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注' AFTER `ip`;

ALTER TABLE `transfer_order` 
MODIFY COLUMN `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '第三方返回备注' AFTER `memo`;