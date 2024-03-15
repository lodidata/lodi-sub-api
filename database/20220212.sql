
#代付

INSERT INTO `transfer_config` (`id`, `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`) VALUES (6, 'YFBPAY', 0, 'PROMPTPAY', NULL, '0', NULL, 1, 'enabled', 'Byh8WRqiXkWsKhHkcymPaw', 'chKOkPEU5ESZNminVEPuA', NULL, NULL, '8806', NULL, NULL, 0, 0, 0, NULL, NULL, '{\"KBANK\":\"KBANK\",\"SCB\":\"SCB\",\"BBL\":\"BBL\",\"BAY\":\"BAY\",\"KTB\":\"KTB\",\"TMB\":\"TMB\",\"GSB\":\"GSB\",\"TBANK\":\"TBANK\",\"BAAC\":\"BAAC\",\"CIMB\":\"CIMB\",\"UOBT\":\"UOBT\",\"LHBANK\":\"LHBANK\",\"TISCO\":\"TISCO\",\"CITI\":\"CITI\",\"GHB\":\"GHB\",\"ICBC\":\"ICBC\",\"TCRB\":\"TCRB\",\"SCBT\":\"SCBT\",\"HSBC\":\"HSBC\",\"SMBC\":\"SMBC\",\"MHCB\":\"MHCB\",\"ISBT\":\"ISBT\",\"DB\":\"DB\",\"KK\":\"KK\"}', 'https://th.ththpas.com');

INSERT INTO `transfer_config` (`name`, `code`, `sort`, `status`, `key`, `pub_key`, `partner_id`, `max_money`, `min_money`, `bank_list`, `request_url`) VALUES ('BCQR', 'BCQR', 3, 'enabled', 'b3598c6e579f4b138c291550e8d467a9', 'b3598c6e579f4b138c291550e8d467a9', '764d16fd3e3a470aba7c16b3ad0ec2ea', 10000000, 10000, '{\"KBANK\":\"KBANK\",\"SCB\":\"SCB\",\"BBL\":\"BBL\",\"BAY\":\"BAY\",\"KTB\":\"KTB\",\"TMB\":\"TMB\",\"GSB\":\"GSB\",\"TBANK\":\"TBANK\",\"BAAC\":\"BAAC\",\"CIMB\":\"CIMB\",\"UOBT\":\"UOBT\",\"LHBANK\":\"LHBANK\",\"TISCO\":\"TISCO\",\"CITI\":\"CITI\",\"GHB\":\"GHB\",\"ICBC\":\"ICBC\",\"TCRB\":\"TCRB\",\"SCBT\":\"SCBT\",\"HSBC\":\"HSBC\",\"SMBC\":\"SMBC\",\"MHCB\":\"MHCB\",\"ISBT\":\"ISBT\",\"DB\":\"DB\",\"KK\":\"KK\"}', 'https://www.xindovnd.com');

#增加图片配置

ALTER TABLE `pay_config` 
ADD COLUMN `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '图片' AFTER `type`;

#商户号字段太小
ALTER TABLE `pay_config` 
MODIFY COLUMN `partner_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商户号' AFTER `img`;

#promptpay支付配置
INSERT INTO `dev_game`.`pay_config` (`id`, `name`, `type`, `img`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`) VALUES (2, 'YFBPAY', 'promptpay', NULL, '8806', 'Byh8WRqiXkWsKhHkcymPaw', 'chKOkPEU5ESZNminVEPuA', 'https://th.ththpas.com', 100, 5000000, '52.175.72.66', 'h5', '2022-02-14 16:05:52', 'enabled', 0, 0, 0, 0, 0, 'json', NULL, '');


#京都支付配置
INSERT INTO `dev_game`.`pay_config` (`id`, `name`, `type`, `img`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`) VALUES (3, 'BCQR', 'bcqr', NULL, '764d16fd3e3a470aba7c16b3ad0ec2ea', 'b3598c6e579f4b138c291550e8d467a9', 'b3598c6e579f4b138c291550e8d467a9', 'https://www.xindovnd.com', 10000, 10000000, NULL, 'h5', '2022-02-12 16:02:46', 'enabled', 0, 0, 0, 0, 3, 'json', NULL, '');


INSERT INTO `funds_channel` (`type_id`, `title`, `desc`) VALUES (2, 'YFBPAY', 'YFBPAY');
INSERT INTO `funds_channel` (`type_id`, `title`, `desc`) VALUES (3, 'BCQR', 'BCQR');

INSERT INTO level_online (level_id, pay_plat) VALUES
(3,'bigtpay'),
(4,'bigtpay'),
(5,'bigtpay'),
(1,'promptpay'),
(2,'promptpay'),
(3,'promptpay'),
(4,'promptpay'),
(5,'promptpay'),
(6,'promptpay'),
(7,'promptpay'),
(8,'promptpay'),
(1,'BCQR'),
(2,'BCQR'),
(3,'BCQR'),
(4,'BCQR'),
(5,'BCQR'),
(6,'BCQR'),
(7,'BCQR'),
(8,'BCQR');