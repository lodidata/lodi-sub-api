
#添加支付回调域名配置,默认为空
ALTER TABLE `pay_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成支付回调域名' AFTER `pay_type`;


#添加代付回调域名配置
ALTER TABLE `transfer_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成代付回调域名' AFTER `request_url`;

#支付，代付日志
ALTER TABLE `pay_request_third_log` ADD COLUMN `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付类型代码' AFTER `id`;

ALTER TABLE `transfer_log`
    ADD COLUMN `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式' AFTER `order_id`,
    ADD COLUMN `payUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '请示接口地址' AFTER `payUrl`;


#++++++++++++++bet77 设置支付回调地址
update pay_config set pay_callback_domain='http://api-www.afrumc.com' where type='yypay';

update transfer_config set pay_callback_domain='http://api-admin.bet77.lol' where code='TGPAY';

