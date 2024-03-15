##YYPAY支付
INSERT INTO `pay_config` (`id`, `name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`,`max_money`,`status`,`sort`,`ip`) 
VALUE(10,'TGPAY','tgpay','xks888','ShDmkJUukTMQZosk', 'ShDmkJUukTMQZosk', 'https://api.tigerpay.online', 10000, 5000000, 'enabled', 14,'172.105.35.212');

INSERT INTO `funds_channel` (`type_id`,`title`,`desc`, `show`) VALUE(10, 'TGPAY', 'TGPAY', 'online');

INSERT INTO `level_online` (`level_id`, `pay_plat`) VALUES(1, 'tgpay'),(2, 'tgpay'),(3, 'tgpay'),(4, 'tgpay'),(5, 'tgpay'),(6, 'tgpay'),(7, 'tgpay'),(8, 'tgpay');
