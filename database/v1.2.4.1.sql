ALTER TABLE `user_data_review` ADD COLUMN `image` json NULL COMMENT '图片' AFTER `salt`;
ALTER TABLE `user_data_review` ADD COLUMN `type_id` tinyint(2) UNSIGNED NULL COMMENT '审核类型（1：银行信息；2：登陆密码；3：PIN密码；4：开户名）' AFTER `image`;

ALTER TABLE `user_level` ADD COLUMN `bankcard_sum` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '银行卡绑定数' AFTER `comment`;

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (385, 146, '充值用户流水占比', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (386, 385, '查询', 'GET', '/report/deposit_ratio', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (387, 385, '导出', 'GET', '/report/deposit_ratio/export', NULL, 1);

#彩金活动菜单
UPDATE `admin_user_role_auth` SET `pid` = 319 WHERE `id` = 321;
UPDATE `admin_user_role_auth` SET `pid` = 319 WHERE `id` = 322;
UPDATE `admin_user_role_auth` SET `pid` = 319 WHERE `id` = 323;
UPDATE `admin_user_role_auth` SET `pid` = 319 WHERE `id` = 324;

#提现审核优化
alter table funds_withdraw change `status` `status` enum('canceled','rejected','paid','prepare','pending','failed','refused','confiscate','obligation','lock','oblock') CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '#状态(rejected:已拒绝, refused:已取消，paid:已支付， prepare:准备支付, pending:待处理，failed：支付失败,canceled:用户取消提款,confiscate:没收,obligation:待付款,lock:锁定,oblock:待付款锁定)';
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`status`) values(412,95,'转账记录-失败','PATCH','/thirdAdvance/transfer',1);

#菲版银行列表排序
update `bank` set sort='1' where shortname='Globe Gcash';
update `bank` set sort='2' where shortname='GrabPay';
update `bank` set sort='3' where shortname='BPI';
update `bank` set sort='4' where shortname='Landbank of the Philippines';
update `bank` set sort='5' where shortname='Paymaya Philippines, Inc.';
update `bank` set sort='6' where shortname='Banco De Oro Unibank, Inc.';
update `bank` set sort='7' where shortname='Metropolitan Bank and Trust Co';
update `bank` set sort='8' where shortname='PNB';
update `bank` set sort='9' where shortname='Rizal Commercial Banking Corporation';
update `bank` set sort='10' where shortname='BDO Network Bank, Inc.';
update `bank` set sort='11' where shortname='BOC';
update `bank` set sort='12' where shortname='Chinabank';
update `bank` set sort='13' where shortname='SBC';
update `bank` set sort='14' where shortname='UnionBank EON';
update `bank` set sort='15' where shortname='EB';
update `bank` set sort='16' where shortname='BPI Direct Banko, Inc., A Savings Bank';
update `bank` set sort='17' where shortname='AUB';
update `bank` set sort='18' where shortname='Cebuana Lhuillier Rural Bank, Inc.';
update `bank` set sort='19' where shortname='RB';
update `bank` set sort='20' where shortname='PTC';
update `bank` set sort='21' where shortname='PBC';
update `bank` set sort='22' where shortname='CTBC';
update `bank` set sort='23' where shortname='Maybank Philippines, Inc.';
update `bank` set sort='24' where shortname='PVB';
update `bank` set sort='25' where shortname='CBS';
update `bank` set sort='26' where shortname='CBC';
update `bank` set sort='27' where shortname='CSB';
update `bank` set sort='28' where shortname='Starpay';
update `bank` set sort='29' where shortname='DBI';
update `bank` set sort='30' where shortname='ERB';
update `bank` set sort='31' where shortname='ISLA Bank (A Thrift Bank), Inc.';
update `bank` set sort='32' where shortname='ING';
update `bank` set sort='33' where shortname='MB';
update `bank` set sort='34' where shortname='ESB';
update `bank` set sort='35' where shortname='Omnipay';
update `bank` set sort='36' where shortname='PBB';
update `bank` set sort='37' where shortname='United Coconut Planters Bank';
update `bank` set sort='38' where shortname='Wealth Development Bank, Inc.';
update `bank` set sort='39' where shortname='Yuanta Savings Bank, Inc.';
update `bank` set sort='40' where shortname='PSB';
update `bank` set sort='41' where shortname='Queen City Development Bank, Inc.';
update `bank` set sort='42' where shortname='PB';
update `bank` set sort='43' where shortname='Bangko Mabuhay (A Rural Bank), Inc.';
update `bank` set sort='44' where shortname='SBA';
update `bank` set sort='45' where shortname='UCPB SAVINGS BANK';
update `bank` set sort='46' where shortname='Binangonan Rural Bank Inc';
update `bank` set sort='47' where shortname='SSB';
update `bank` set sort='48' where shortname='RBG';
update `bank` set sort='49' where shortname='Partner Rural Bank (Cotabato), Inc.';
update `bank` set sort='50' where shortname='Camalig';
update `bank` set sort='51' where shortname='ALLBANK (A Thrift Bank), Inc.';
update `bank` set sort='52' where shortname='Allied Banking Corp';

#菲版银行卡图标路径
update `bank` set logo='/lodi/bank/Gcash.png' where code='Gcash';
update `bank` set logo='/lodi/bank/GrabPay.png' where code='GrabPay';
update `bank` set logo='/lodi/bank/BPI.png' where code='BPI';
update `bank` set logo='/lodi/bank/LandbankofthePhilippines.png' where code='Landbank of the Philippines';
update `bank` set logo='/lodi/bank/PaymayaPhilippinesInc.png' where code='Paymaya Philippines, Inc.';
update `bank` set logo='/lodi/bank/MetropolitanBankandTrustCo.png' where code='Metropolitan Bank and Trust Co';
update `bank` set logo='/lodi/bank/PNB.png' where code='PNB';
update `bank` set logo='/lodi/bank/RizalCommercialBankingCorporation.png' where code='Rizal Commercial Banking Corporation';
update `bank` set logo='/lodi/bank/BDONetworkBankInc.png' where code='BDO Network Bank, Inc.';
update `bank` set logo='/lodi/bank/BOC.png' where code='BOC';
update `bank` set logo='/lodi/bank/Chinabank.png' where code='Chinabank';
update `bank` set logo='/lodi/bank/SBC.png' where code='SBC';
update `bank` set logo='/lodi/bank/UnionBankEON.png' where code='UnionBank EON';
update `bank` set logo='/lodi/bank/EB.png' where code='EB';
update `bank` set logo='/lodi/bank/BPIDirectBankoIncASavingsBank.png' where code='BPI Direct Banko, Inc., A Savings Bank';
update `bank` set logo='/lodi/bank/AUB.png' where code='AUB';
update `bank` set logo='/lodi/bank/CebuanaLhuillierRuralBankInc.png' where code='Cebuana Lhuillier Rural Bank, Inc.';
update `bank` set logo='/lodi/bank/RB.png' where code='RB';
update `bank` set logo='/lodi/bank/PTC.png' where code='PTC';
update `bank` set logo='/lodi/bank/PBC.png' where code='PBC';
update `bank` set logo='/lodi/bank/CTBC.png' where code='CTBC';
update `bank` set logo='/lodi/bank/MaybankPhilippinesInc.png' where code='Maybank Philippines, Inc.';
update `bank` set logo='/lodi/bank/PVB.png' where code='PVB';
update `bank` set logo='/lodi/bank/CBS.png' where code='CBS';
update `bank` set logo='/lodi/bank/CSB.png' where code='CSB';
update `bank` set logo='/lodi/bank/Starpay.png' where code='Starpay';
update `bank` set logo='/lodi/bank/DBI.png' where code='DBI';
update `bank` set logo='/lodi/bank/ERB.png' where code='ERB';
update `bank` set logo='/lodi/bank/ISLABankAThriftBankInc.png' where code='ISLA Bank (A Thrift Bank), Inc.';
update `bank` set logo='/lodi/bank/ING.png' where code='ING';
update `bank` set logo='/lodi/bank/MB.png' where code='MB';
update `bank` set logo='/lodi/bank/ESB.png' where code='ESB';
update `bank` set logo='/lodi/bank/Omnipay.png' where code='Omnipay';
update `bank` set logo='/lodi/bank/PBB.png' where code='PBB';
update `bank` set logo='/lodi/bank/WealthDevelopmentBankInc.png' where code='Wealth Development Bank, Inc.';
update `bank` set logo='/lodi/bank/YuantaSavingsBankInc.png' where code='Yuanta Savings Bank, Inc.';
update `bank` set logo='/lodi/bank/PSB.png' where code='PSB';
update `bank` set logo='/lodi/bank/QueenCityDevelopmentBankInc.png' where code='Queen City Development Bank, Inc.';
update `bank` set logo='/lodi/bank/PB.png' where code='PB';
update `bank` set logo='/lodi/bank/BangkoMabuhayARuralBankInc.png' where code='Bangko Mabuhay (A Rural Bank), Inc.';
update `bank` set logo='/lodi/bank/SBA.png' where code='SBA';
update `bank` set logo='/lodi/bank/UCPBSAVINGSBANK.png' where code='UCPB SAVINGS BANK';
update `bank` set logo='/lodi/bank/BinangonanRuralBankInc.png' where code='Binangonan Rural Bank Inc';
update `bank` set logo='/lodi/bank/SSB.png' where code='SSB';
update `bank` set logo='/lodi/bank/RBG.png' where code='RBG';
update `bank` set logo='/lodi/bank/PartnerRuralBankCotabatoInc.png' where code='Partner Rural Bank (Cotabato), Inc.';
update `bank` set logo='/lodi/bank/Camalig.png' where code='Camalig';
update `bank` set logo='/lodi/bank/ALLBANKAThriftBankInc.png' where code='ALLBANK (A Thrift Bank), Inc.';
update `bank` set logo='/lodi/bank/AlliedBankingCorp.png' where code='Allied Banking Corp';
UPDATE `bank` SET `logo` = '/lodi/bank/CTBC.png' WHERE `code` = 'CBC';
UPDATE `bank` SET `logo` = '/lodi/bank/UCPBSAVINGSBANK.png' WHERE `code` = 'United Coconut Planters Bank';
UPDATE `bank` SET `logo` = '/lodi/bank/BDONetworkBankInc.png' WHERE `code` = 'Banco De Oro Unibank, Inc.';

#将原有提现审核权限停止使用
UPDATE `admin_user_role_auth` SET `status` = 0 WHERE `id` = 93;
#拆分新的提现审核接口权限
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (399, 89, '拒绝打款', 'PATCH', '/cash/newwithdraw/rejected', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (400, 89, '审核通过', 'PATCH', '/cash/newwithdraw/obligation', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (401, 89, '锁定', 'PATCH', '/cash/newwithdraw/lock', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (402, 89, '解锁', 'PATCH', '/cash/newwithdraw/unlock', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (403, 89, '没收', 'PATCH', '/cash/newwithdraw/confiscate', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (404, 89, '打款成功', 'PATCH', '/cash/newwithdraw/paid', NULL, 1);
#将代付接口权限修改到提现审核下
UPDATE `admin_user_role_auth` SET `pid` = 89 WHERE `id` = 98;

#活动管理-幸运轮盘列表
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (405, 183, '幸运轮盘列表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (406, 405, '幸运轮盘列表-查询', 'GET', '/active/applys/fortune', NULL, 1);

#活动管理-用户申请列表
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (407, 183, '用户申请列表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (408, 407, '查询', 'GET', '/active/apply/user', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (409, 407, '导出', 'GET', '/active/apply/user/export', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (410, 407, '通过申请', 'PUT', '/active/apply/user/pass', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (411, 407, '拒绝申请', 'PUT', '/active/apply/user/reject', NULL, 1);

#泰版银行卡图标路径
update `bank` set logo='/ncg/bank/BAAC.png' where code='BAAC';
update `bank` set logo='/ncg/bank/BAY.png' where code='BAY';
update `bank` set logo='/ncg/bank/BBL.png' where code='BBL';
update `bank` set logo='/ncg/bank/GSB.png' where code='GSB';
update `bank` set logo='/ncg/bank/KBANK.png' where code='KBANK';
update `bank` set logo='/ncg/bank/KKB.png' where code='KKB';
update `bank` set logo='/ncg/bank/KTB.png' where code='KTB';
update `bank` set logo='/ncg/bank/SCB.png' where code='SCB';
update `bank` set logo='/ncg/bank/TTB.png' where code='TTB';
update `bank` set logo='/ncg/bank/UOB.png' where code='UOB';
UPDATE `bank` SET `logo` = '/ncg/bank/BOA.png' WHERE code='BOA';
UPDATE `bank` SET `logo` = '/ncg/bank/BOC.png' WHERE code='BOC';
UPDATE `bank` SET `logo` = '/ncg/bank/BNPP.png' WHERE code='BNPP';
UPDATE `bank` SET `logo` = '/ncg/bank/CIMB.png' WHERE code='CIMB';
UPDATE `bank` SET `logo` = '/ncg/bank/CITI.png' WHERE code='CITI';
UPDATE `bank` SET `logo` = '/ncg/bank/DB.png' WHERE code='DB';
UPDATE `bank` SET `logo` = '/ncg/bank/GHB.png' WHERE code='GHB';
UPDATE `bank` SET `logo` = '/ncg/bank/ICBC.png' WHERE code='ICBC';
UPDATE `bank` SET `logo` = '/ncg/bank/TIBT.png' WHERE code='TIBT';
UPDATE `bank` SET `logo` = '/ncg/bank/CHAS.png' WHERE code='CHAS';
UPDATE `bank` SET `logo` = '/ncg/bank/LHBA.png' WHERE code='LHBA';
UPDATE `bank` SET `logo` = '/ncg/bank/MEGA.png' WHERE code='MEGA';
UPDATE `bank` SET `logo` = '/ncg/bank/MHCB.png' WHERE code='MHCB';
UPDATE `bank` SET `logo` = '/ncg/bank/SCBT.png' WHERE code='SCBT';
UPDATE `bank` SET `logo` = '/ncg/bank/SMTB.png' WHERE code='SMTB';
UPDATE `bank` SET `logo` = '/ncg/bank/HSBC.png' WHERE code='HSBC';
UPDATE `bank` SET `logo` = '/ncg/bank/SMBC.png' WHERE code='SMBC';
UPDATE `bank` SET `logo` = '/ncg/bank/TCRB.png' WHERE code='TCRB';
UPDATE `bank` SET `logo` = '/ncg/bank/TISCO.png' WHERE code='TISCO';

#添加索引
ALTER TABLE rpt_user ADD INDEX idx_first_deposit (first_deposit);