# 修改规则发放时间类型
ALTER TABLE `active_rule`
MODIFY COLUMN `issue_time` time(0) NULL DEFAULT NULL COMMENT '发放时间(时分秒)';

#FC新增游戏
insert into game_3th(kind_id,game_id,game_name,`rename`,type,alias) values
(21008,94,'STAR HUNTER','星际捕鱼','BY','STARHUNTER'),
(22038,93,'GLORY OF ROME','罗马竞技','Slot','GLORYOFROME'),
(27001,93,'MONEY TREE DOZER','钱树推币机','Slot','MONEYTREEDOZER'),
(27002,93,'CIRCUS DOZER','马戏团推币机','Slot','CIRCUSDOZER'),
(27003,93,'FA CHAI DOZER','发财推币机','Slot','FACHAIDOZER'),
(27005,93,'LIGHTNING BOMB','一触即发','Slot','LIGHTNINGBOMB');

#FC新游戏更新图片
update game_3th set game_img=CONCAT('https://update.thxxsso.com/lodi/game/vert/fc/',kind_id,'.png') where game_id in (93,94) and game_img is null;

#新代理返佣表删除唯一索引
ALTER TABLE `new_bkge`
DROP INDEX `udx_date_user_id`;

#新增活动模板
INSERT INTO `active_template`(`id`, `name`, `description`, `state`, `created_uid`, `created`, `updated`)
VALUES (8, 'Weekly Rebate Promotion', 'Weekly Rebate Promotion', '', NULL, '2022-04-13 14:26:45', '2022-04-25 14:51:11');

INSERT INTO `active_template`(`id`, `name`, `description`, `state`, `created_uid`, `created`, `updated`)
VALUES (9, 'Monthly Rebate Promotion', 'Monthly Rebate Promotion', '', NULL, '2022-04-13 14:27:19', '2022-04-25 14:51:16');

#新增活动类型
INSERT INTO `active_type`(`id`, `name`, `description`, `sort`, `image`, `status`, `created_uid`, `updated_uid`, `created`, `updated`)
VALUES (8, 'Weekly Rebate Promotion', 'Weekly Rebate Promotion', 8, '', 'disabled', NULL, NULL, '2022-04-13 14:14:56', '2022-04-25 14:51:51');


INSERT INTO `active_type`(`id`, `name`, `description`, `sort`, `image`, `status`, `created_uid`, `updated_uid`, `created`, `updated`)
VALUES (9, 'Monthly Rebate Promotion', 'Monthly Rebate Promotion', 9, '', 'disabled', NULL, NULL, '2022-04-21 16:55:50', '2022-04-25 14:51:54');


#活动赠送方式加上联系客服
ALTER TABLE `active`
MODIFY COLUMN `state` set('manual','auto','contact') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '集合(manual:手动, auto:自动参与,contact:联系客服)' AFTER `status`;


##dingpei增加支付类型
UPDATE `transfer_config` SET `bank_list`= '{"AUB":"AUB","UnionBank EON":"UnionBank EON","Starpay":"Starpay","EB":"EB","ESB":"ESB","MB":"MB","ERB":"ERB","PB":"PB","PBC":"PBC","PBB":"PBB","PNB":"PNB","PSB":"PSB","PTC":"PTC","PVB":"PVB","RBG":"RBG","Rizal Commercial Banking Corporation":"Rizal Commercial Banking Corporation","RB":"RB","SBC":"SBC","SBA":"SBA","SSB":"SSB","UCPB SAVINGS BANK":"UCPB SAVINGS BANK","Queen City Development Bank, Inc.":"Queen City Development Bank, Inc.","United Coconut Planters Bank":"United Coconut Planters Bank","Wealth Development Bank, Inc.":"Wealth Development Bank, Inc.","Yuanta Savings Bank, Inc.":"Yuanta Savings Bank, Inc.","GrabPay":"GrabPay","Banco De Oro Unibank, Inc.":"Banco De Oro Unibank, Inc.","Bangko Mabuhay (A Rural Bank), Inc.":"Bangko Mabuhay (A Rural Bank), Inc.","BOC":"BOC","CTBC":"CTBC","Chinabank":"Chinabank","CBS":"CBS","CBC":"CBC","ALLBANK (A Thrift Bank), Inc.":"ALLBANK (A Thrift Bank), Inc.","BDO Network Bank, Inc.":"BDO Network Bank, Inc.","Binangonan Rural Bank Inc":"Binangonan Rural Bank Inc","Camalig":"Camalig","DBI":"DBI","Gcash":"Gcash","Cebuana Lhuillier Rural Bank, Inc.":"Cebuana Lhuillier Rural Bank, Inc.","ISLA Bank (A Thrift Bank), Inc.":"ISLA Bank (A Thrift Bank), Inc.","Landbank of the Philippines":"Landbank of the Philippines","Maybank Philippines, Inc.":"Maybank Philippines, Inc.","Metropolitan Bank and Trust Co":"Metropolitan Bank and Trust Co","Omnipay":"Omnipay","Partner Rural Bank (Cotabato), Inc.":"Partner Rural Bank (Cotabato), Inc.","Paymaya Philippines, Inc.":"Paymaya Philippines, Inc.","Allied Banking Corp":"Allied Banking Corp","ING":"ING","BPI Direct Banko, Inc., A Savings Bank":"BPI Direct Banko, Inc., A Savings Bank","CSB":"CSB","BPI":"BPI"}' WHERE `name` = 'DINGPEI';




