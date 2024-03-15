##YYPAY支付
INSERT INTO `pay_config` (`id`, `name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`,`max_money`,`status`,`sort`,`ip`) 
VALUE(8,'YYPAY','yypay','lodibet','Fgwa2FKRaWyxVeNQpzRF3m7tbUQGEvjy', 'Fgwa2FKRaWyxVeNQpzRF3m7tbUQGEvjy', 'https://api.yypay.pro', 0, 0, 'enabled', 14,'192.46.209.100');

INSERT INTO `funds_channel` (`type_id`,`title`,`desc`, `show`) VALUE(8, 'YYPAY', 'YYPAY', 'online');

INSERT INTO `level_online` (`level_id`, `pay_plat`) VALUES(1, 'yypay'),(2, 'yypay'),(3, 'yypay'),(4, 'yypay'),(5, 'yypay'),(6, 'yypay'),(7, 'yypay'),(8, 'yypay');



