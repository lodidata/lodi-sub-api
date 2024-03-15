#top646
#qxpay-直连
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY', 'qxpay', 'top646', '7ZVfjxWoc8iYQKlp1wXyKCIJTga1dBof2zPL8BACssG6lMHcGHTGJ7WnPbyj', 'kLox6Em1fRuWkyq3xCPJCQIupqamUufmMMZ07W1I3HzaB8Vpa3ejCSJPN8lu', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY' as name,0 as status from pay_config where type='qxpay';

INSERT INTO `transfer_config` (`name`,`balance`,`code`,`ver`,`app_id`,`app_secret`,`sort`,`status`,`key`,`pub_key`,`token`,`terminal`,`partner_id`,`url_notify`,`url_return`,`max_money`,`min_money`,`fee`,`email`,`request_code`,`bank_list`,`request_url`,`params`)VALUES('QXPAY',0,'QXPAY',NULL,'0',NULL,11,'default','7ZVfjxWoc8iYQKlp1wXyKCIJTga1dBof2zPL8BACssG6lMHcGHTGJ7WnPbyj','kLox6Em1fRuWkyq3xCPJCQIupqamUufmMMZ07W1I3HzaB8Vpa3ejCSJPN8lu',NULL,NULL,'top646',NULL,NULL,0,0,0,NULL,NULL,'{"Gcash":"Gcash"}','https://qxpay168.com','[]');

#qxpay-扫码
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY2', 'qxpay2', 'top646', 'FSYE6OtbDI29CddoCNkGQdUsDVhgAXv7hWcd1fn2K2azysEmhG4QpTdAhqSY', 'W4X6Ho2wNexau1RE98tEUv7yGDttMh8P9MhzSYUhf1U9yUkR7bQhm2uuUY40', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay2' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY-QR' as name,0 as status from pay_config where type='qxpay2';


#qxpay-paymaya
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY3', 'qxpay3', 'top646', 'dXJbECVXIwkAyvRipJCThRYemONQiKziFc5SfDYmec9a1uwPsRxNGtGB4rI7', 'OhfGmJDrPm9V3vgsG1tfqvprZQ1bIwPlXqnSRtDNk6MHDAEjvffLOJ6J5dGg', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay3' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY-PayMaya' as name,0 as status from pay_config where type='qxpay3';



#ph646
#qxpay-直连
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY', 'qxpay', 'ph646', 'JRnAElb2mj4IDbX5H12XKODZPCPyzZ2mjSmX4qlNmkWRRiBbDPu6WktYO4BA', 'YHAxwsJzNEvX88qWYFgPP5nbg5gBzQ6IzMBNfryayuGnCcKfbxW7poSUl0Le', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY' as name,0 as status from pay_config where type='qxpay';

INSERT INTO `transfer_config` (`name`,`balance`,`code`,`ver`,`app_id`,`app_secret`,`sort`,`status`,`key`,`pub_key`,`token`,`terminal`,`partner_id`,`url_notify`,`url_return`,`max_money`,`min_money`,`fee`,`email`,`request_code`,`bank_list`,`request_url`,`params`)VALUES('QXPAY',0,'QXPAY',NULL,'0',NULL,11,'default','JRnAElb2mj4IDbX5H12XKODZPCPyzZ2mjSmX4qlNmkWRRiBbDPu6WktYO4BA','YHAxwsJzNEvX88qWYFgPP5nbg5gBzQ6IzMBNfryayuGnCcKfbxW7poSUl0Le',NULL,NULL,'top646',NULL,NULL,0,0,0,NULL,NULL,'{"Gcash":"Gcash"}','https://qxpay168.com','[]');

#qxpay-扫码
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY2', 'qxpay2', 'ph646', 'uDLBJjZpmoyNYk5gnMRBsFkM5PrRo5etA0ShuNdgdZy7lWQNm2RfcyH7FOa1', '2IGBPbha3YnD1CQy7UH5vKfg7g4m2jml2okYmOXckNxoZBjbxluaLbuIFG1O', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay2' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY-QR' as name,0 as status from pay_config where type='qxpay2';


#qxpay-paymaya
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY3', 'qxpay3', 'ph646', 'etmbiPEoaTZQl62tE8BKiCIgdqgjQlQkGf1Lfs2PjYKto1cNDgBIZdWnt2cO', 'uKiSIMDPnNPIG2xetsRoOS66IUsn0PZ4QN0CDrb80TxKNlyQBvZwOWFFrJIc', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay3' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY-PayMaya' as name,0 as status from pay_config where type='qxpay3';


#lodi646
#qxpay-paymaya
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('QXPAY3', 'qxpay3', 'ph646', 'etmbiPEoaTZQl62tE8BKiCIgdqgjQlQkGf1Lfs2PjYKto1cNDgBIZdWnt2cO', 'EIS6lrPCWvqyJPsEO32xDqBuqOXWHxalFBxza7Pvpoyo1ad7LmGLqgPRcac8', 'https://qxpay168.com', 0, 0, '35.76.106.131', 'h5', '2023-01-14 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) SELECT id,'qxpay3' FROM user_level;

INSERT INTO `payment_channel` (`pay_channel_id`, `pay_config_id`, `name`,`status`) select '6' as pay_channel_id, id as pay_config_id, 'QXPAY-PayMaya' as name,0 as status from pay_config where type='qxpay3';

