#80JILI
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`, `pay_type`, `pay_callback_domain`, `params`) VALUES ('WJHTPAY', 'wjhtpay', 'HTWJ80jl08', '89ea6269b8521336aacc8059efc6a5fd', '89ea6269b8521336aacc8059efc6a5fd', 'https://apihtpay.gazer8.info', 0, 0, '18.138.154.150,54.255.17.78', 'h5', '2023-04-23 19:16:37', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '', '', '', '[]');

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`,`type`,min_money,max_money,rechage_money) select '1' as pay_channel_id, id as pay_config_id, 'WJHTPAY' as name,0 as status,'3' as `type`,0 as min_money,0 as max_money,'{"0":10000,"1":20000,"2":30000,"3":50000,"4":100000}' as rechage_money from pay_config where type='wjhtpay';

insert into level_payment (level_id,payment_id) SELECT t1.id AS level_id, t2.id AS payment_id FROM user_level t1 JOIN payment_channel t2 where t2.name='WJHTPAY';

INSERT INTO  `transfer_config` (`name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`, `pay_callback_domain`, `params`) VALUES ('WJHTPAY', 0, 'WJHTPAY', NULL, '0', NULL, 1, 'default', '89ea6269b8521336aacc8059efc6a5fd', '89ea6269b8521336aacc8059efc6a5fd', NULL, NULL, 'HTWJ80jl08', NULL, NULL, 5000000, 10000, 0, NULL, NULL, '{"Gcash":"Gcash","PMP":"PayMaya Philippines","UBP":"Union Bank of the Philippines"}', 'https://apihtpay.gazer8.info', '', '[]');



