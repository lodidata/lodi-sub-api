
#支付
insert into pay_config(`name`,`type`,`partner_id`,`key`,`pub_key`,`payurl`,`ip`,`show_type`,`status`,`sort`,`return_type`,`link_data`,`pay_type`) values('DIDIPAY','didipay','lodi6688','9e36098982c044c8e38b7cb97eb95784','9e36098982c044c8e38b7cb97eb95784','https://didipay.gcash.cash','13.251.58.196,3.0.23.11','h5','disabled',15,'json','https://www.lodigame.com/','');
insert into level_online(`level_id`, `pay_plat`) values(1,'didipay'),(2,'didipay'),(3,'didipay'),(4,'didipay'),(5,'didipay'),(6,'didipay'),(7,'didipay'),(8,'didipay'),(9,'didipay'),(10,'didipay'),(11,'didipay'),(12,'didipay');

#代付
INSERT INTO `transfer_config`( `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`, `pay_callback_domain`) VALUES ('DIDIPAY', 0, 'DIDIPAY', NULL, '0', NULL, 1, 'disabled', '9e36098982c044c8e38b7cb97eb95784', '9e36098982c044c8e38b7cb97eb95784', NULL, NULL, 'lodi6688', NULL, NULL, 0, 0, 500, NULL, NULL, '{\"Gcash\":\"Gcash\"}', 'https://didipay.gcash.cash', '');