#系统设置sql
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'system', 'ip开关设置', 'bool', 'register_limit_ip_switch', '0', '1开，0关', 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('system', 'ip地址限制设置', 'string', 'register_limit_ip_list', '', NULL, 'enabled', '2022-07-13 16:37:29');

#菜单id
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (294, 123, 'ip设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (295, 294, '查询', 'GET', '/ip/register', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (296, 294, '编辑', 'PUT', '/ip/register', NULL, 1);

ALTER TABLE `rpt_deposit_withdrawal_day`
    ADD COLUMN `new_register_withdraw_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增取款金额' AFTER `new_register_deposit_amount`;

ALTER TABLE `admin_index_third`
    ADD COLUMN `new_register_withdraw_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增充值金额' AFTER `recharge_first_avg`;



#+++++++++++++++++++++++++++++++++lodi子站

INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `status`, `switch`, `across_status`) VALUES (103, 15, 'DG', 'DG', 'DG', 'DG视讯', 'disabled', 'enabled', 'enabled');

INSERT INTO `game_3th` (`kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`) VALUES ('dg', 103, 'DreamGame', 'DG游戏', 'LIVE', 'DreamGame');

ALTER TABLE `user_dml` ADD COLUMN `DG` int(10) UNSIGNED NULL DEFAULT 0;

create table game_order_dg(
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `order_number` varchar(64) NOT NULL DEFAULT '' COMMENT '注单ID',
  `lobbyId` int(11) NOT NULL DEFAULT 0 COMMENT '游戏大厅号 1:旗舰厅；3，4:现场厅；5:欧美厅,7:国际厅,8:区块链厅',
  `tableId` int(11) NOT NULL DEFAULT 0 COMMENT '游戏桌号',
  `shoeId` int(11) NOT NULL DEFAULT 0 COMMENT '游戏靴号',
  `playId` int(11) NOT NULL DEFAULT 0 COMMENT '游戏局号',
  `gameType` tinyint(4) NOT NULL DEFAULT 0 COMMENT '游戏类型',
  `GameId` tinyint(4) NOT NULL DEFAULT 0 COMMENT '游戏Id',
  `memberId` int(11) NOT NULL DEFAULT 0 COMMENT '会员Id',
  `parentId` int(11) NOT NULL DEFAULT 0 COMMENT '上级ID',
  `betTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '游戏下注时间',
  `calTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '游戏结算时间',
  `winOrLoss` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '派彩金额 (输赢应扣除下注金额)',
  `winOrLossz` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '好路追注派彩金额',
  `betPoints` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '下注金额',
  `betPointsz` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '好路追注金额',
  `availableBet` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '有效下注金额',
  `profit` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '输赢=派彩-下注',
  `userName` varchar(64) NOT NULL DEFAULT '' COMMENT '会员登入账号',
  `result` json NOT NULL COMMENT '游戏结果',
  `betDetail` json NOT NULL COMMENT '下注注单',
  `betDetailz` varchar(500) NOT NULL DEFAULT '' COMMENT '好路追注注单',
  `ip` varchar(16) NOT NULL DEFAULT '' COMMENT '下注时客户端IP',
  `isRevocation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '否结算：1：已结算 2:撤销',
  `balanceBefore` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `parentBetId` int(11) NOT NULL DEFAULT 0 COMMENT '撤销的那比注单的ID',
  `currencyId` int(11) NOT NULL DEFAULT 0 COMMENT '货币ID',
  `deviceType` int(11) NOT NULL DEFAULT 0 COMMENT '下注时客户端类型',
  `pluginid` int(11) NOT NULL DEFAULT 0 COMMENT '追注转账流水号',
  `roadid` tinyint(4) NOT NULL DEFAULT 0 COMMENT '局号ID',
  PRIMARY KEY (`id`, `betTime`) USING BTREE,
  UNIQUE INDEX `uniq_on`(`order_number`, `betTime`) USING BTREE,
  INDEX `idx_bettime`(`betTime`) USING BTREE,
  INDEX `idx_userid`(`user_id`) USING BTREE
)engine=innodb character set=utf8mb4 comment 'DG视讯注单表' ;


alter table pay_config add `params` json NOT NULL COMMENT '支付差异参数';
update pay_config set `params` = '{"countryCode":"PHL","currencyCode":"PHP","paymentType":"902410172001"}' where `type` = 'bpay' limit 1;
alter table transfer_config add `params` json NOT NULL COMMENT '支付差异参数';
update transfer_config set `params` = '{"countryCode":"PHL","currencyCode":"PHP","transferType":"902410175001"}' where `code` = 'BPAY' limit 1;
update transfer_config set `params` = '{"checkCard":1}' where `code` = 'POPPAY' or `code` = 'TOPPAY';


#+++++++++++++++++lodi子站UG图片*********注意子站图片域名***************************

update game_3th set game_img='https://img.caacaya.com/lodi/game/dg/dg.png' where game_id=103;
update game_menu set img='https://img.caacaya.com/lodi/menu/dg.png' where id=103;