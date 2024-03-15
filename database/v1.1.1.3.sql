##新增YOUPAY支付，代付
INSERT INTO `pay_config` (`id`, `name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`,`max_money`,`ip`, `status`,`sort`) 
VALUE(6,'YOUPAY','youpay','42830','qnDkmhs7UG1pFQ8zZK_ZQ','GKQQJIsFy0GyNlhsOdMog', 'https://ph.thejthpay.com', 10000, 2000000, '13.70.43.228', 'enabled', 9);


INSERT INTO `transfer_config` (`id`, `name`, `code`, `status`, `key`, `pub_key`,`partner_id`,`fee`, `max_money`,`min_money`,`bank_list`, `request_url`,`sort`) 
VALUE(6,'YOUPAY','YOUPAY','enabled','qnDkmhs7UG1pFQ8zZK_ZQ','GKQQJIsFy0GyNlhsOdMog', '42830',0, 3000000, 10000,'{"Gcash":"Gcash"}', 'https://ph.thejthpay.com',10);



INSERT INTO `funds_channel` (`type_id`,`title`,`desc`, `show`) VALUE(6, 'YOUPAY', 'YOUPAY', 'online');

INSERT INTO `level_online` (`level_id`, `pay_plat`) VALUES(1, 'youpay'),(2, 'youpay'),(3, 'youpay'),(4, 'youpay'),(5, 'youpay'),(6, 'youpay'),(7, 'youpay'),(8, 'youpay');
