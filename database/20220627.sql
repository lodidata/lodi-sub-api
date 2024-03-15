
INSERT INTO `transfer_config`( `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`)
VALUES ('TGPAY', 0, 'TGPAY', NULL, '0', NULL, 1, 'enabled', '', '', NULL, NULL, '', NULL, NULL, 0, 0, 500, NULL, NULL, '{\"Gcash\":\"Gcash\"}', 'https://api.tigerpay.online');

#添加支付回调域名配置,默认为空
ALTER TABLE `pay_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成支付回调域名' AFTER `pay_type`;

#++++++++++++++bet77 设置支付回调地址
update pay_config set pay_callback_domain='http://api-www.afrumc.com' where type='yypay';


#添加代付回调域名配置
ALTER TABLE `transfer_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成代付回调域名' AFTER `request_url`;
