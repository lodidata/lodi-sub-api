#关闭权限洗码活动、洗码数据
UPDATE admin_user_role_auth set `status` = 0 WHERE  id in (251,254) or pid in (251,254);
UPDATE admin_user_role set auth=REPLACE(auth,',251,252,253,254,255,256','');

#删除bigpay代付

UPDATE `transfer_config` SET `status` = 'deleted' WHERE `id` = 5;


#tupay代付
INSERT INTO `dev_game`.`pay_config` (`id`, `name`, `type`, `img`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`) VALUES (4, 'TUPAY', 'tupay', NULL, 'a852639cc66349bf81efb467d7badda9', 'c39da407ea2940d2bdf6a29f504c643b', 'c39da407ea2940d2bdf6a29f504c643b', 'https://www.tupay168.com', 5000, 5000000, NULL, 'h5', '2022-02-14 16:06:00', 'enabled', 0, 0, 0, 0, 4, 'json', NULL, '');


#tupay支付配置
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `status`, `sort`) VALUES ('TUPAY', 'tupay', 'a852639cc66349bf81efb467d7badda9', 'c39da407ea2940d2bdf6a29f504c643b', 'c39da407ea2940d2bdf6a29f504c643b', 'https://www.tupay168.com', 5000, 5000000, 'enabled', 4);

INSERT INTO `funds_channel` (`type_id`, `title`, `desc`) VALUES (4, 'TUPAY', 'TUPAY');

INSERT INTO level_online (level_id, pay_plat) VALUES
(1,'tupay'),
(2,'tupay'),
(3,'tupay'),
(4,'tupay'),
(5,'tupay'),
(6,'tupay'),
(7,'tupay'),
(8,'tupay');


#会员权限状态增加禁止返佣状态
ALTER TABLE `user` 
MODIFY COLUMN `auth_status` set('refuse_withdraw','refuse_sale','refuse_rebate','refuse_deposit_coupon','refuse_bkge') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'refuse_withdraw(禁止提款),refuse_sale(禁止优惠),refuse_rebate(禁止返水),refuse_deposit_coupon(不要充值优惠),refuse_bkge(禁止返佣)' AFTER `last_login`;

#修改支付名称

UPDATE `pay_config` SET `name` = 'BIGPAY' WHERE `id` = 1;