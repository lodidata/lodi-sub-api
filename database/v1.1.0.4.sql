#添加增加汇总金额不对记录日志 game_order_check_error

CREATE TABLE `game_order_check_error`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '游戏类型',
  `now` datetime NOT NULL COMMENT '开始查询时间',
  `json` json NULL COMMENT '查询条件',
  `error` json NULL COMMENT '错误数据',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单汇总错误表' ROW_FORMAT = Dynamic;

#AutoTopup支付


#代付
INSERT INTO `transfer_config` (`id`, `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`) VALUES (9, 'AutoTopup', 1000000, 'AUTOTOPUP', NULL, '0', NULL, 6, 'enabled', '20zFNEiqdZLwcAu9Cyoggr5IBFf', '20zFNEiqdZLwcAu9Cyoggr5IBFf', NULL, NULL, '20zFNEiqdZLwcAu9Cyoggr5IBFf', NULL, NULL, 0, 0, 500, NULL, NULL, '{\"KBANK\":\"KBANK\",\"SCB\":\"SCB\",\"KTB\":\"KTB\",\"BAY\":\"BAY\",\"GSB\":\"GSB\",\"UOB\":\"UOB\",\"BBL\":\"BBL\",\"CI\":\"CI\",\"LNH\":\"LNH\",\"BAAC\":\"BAAC\",\"OSK\":\"OSK\",\"KNK\":\"KNK\",\"JPK\":\"JPK\",\"CIMB\":\"CIMB\",\"DOIB\":\"DOIB\",\"TISGO\":\"TISGO\",\"TCREDIT\":\"TCREDIT\",\"BNP\":\"BNP\",\"MSU\":\"MSU\",\"MEGA\":\"MEGA\",\"SNT\":\"SNT\",\"CN\":\"CN\",\"USA\":\"USA\",\"ASL\":\"ASL\",\"IZBS\":\"IZBS\",\"TTB\":\"TTB\"}', 'https://api-payment.socialgame.xyz');



#支付
INSERT INTO `pay_config` (`id`, `name`, `type`, `img`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`) VALUES (5, 'AutoTopup', 'autotopup', NULL, 'TEST', '20zFNEiqdZLwcAu9Cyoggr5IBFf', '20zFNEiqdZLwcAu9Cyoggr5IBFf', 'https://api-payment.socialgame.xyz', 0, 0, NULL, 'h5', '2022-02-22 14:06:34', 'enabled', 0, 0, 0, 0, 5, 'json', NULL, '');


INSERT INTO `funds_channel` (`type_id`, `title`, `desc`) VALUES (5, 'AutoTopup', 'AutoTopup');

INSERT INTO level_online (level_id, pay_plat) VALUES
(1,'autotopup'),
(2,'autotopup'),
(3,'autotopup'),
(4,'autotopup'),
(5,'autotopup'),
(6,'autotopup'),
(7,'autotopup'),
(8,'autotopup');


ALTER TABLE `pay_request_third_log` 
ADD COLUMN `pay_url` varchar(255) NULL AFTER `id`;

ALTER TABLE `funds_deposit` 
MODIFY COLUMN `memo` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注' AFTER `ip`;

ALTER TABLE `transfer_order` 
MODIFY COLUMN `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '第三方返回备注' AFTER `memo`;

CREATE TABLE `active_sign_up`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `active_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动id',
  `apply_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '申请时间',
  `first_deposit_time` timestamp NULL COMMENT '第一次充值时间',
  `second_deposit_time` timestamp NULL COMMENT '第二次充值时间',
  `third_deposit_time` timestamp NULL COMMENT '第三次充值时间',
  `times` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参与次数',
  `can_play_all_game` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1:可以玩所有游戏，0：只能玩电子游戏',
  `apply_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参与状态（0：不参与，1：参与）',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) COMMENT = '用户申请参与活动';

ALTER TABLE `bank` 
ADD UNIQUE INDEX `bank_code`(`code`) USING BTREE;

#增加活动模板  电子
INSERT INTO `active_template` (`id`, `name`, `description`, `state`, `created_uid`, `created`, `updated`) VALUES (7, '首次充值送300%', '首次充值送300%', '', 1, '2022-02-19 15:49:32', '2022-02-19 15:49:32');
#增加活动类型  电子
INSERT INTO `active_type` (`id`, `name`, `description`, `sort`, `image`, `status`, `created_uid`, `updated_uid`, `created`, `updated`) VALUES (7, '首次充值送300%', '首次充值送300%', 7, '', 'enabled', NULL, NULL, '2022-02-19 15:53:50', '2022-02-19 15:54:30');


#正式环境AUTOTOPUP配置

UPDATE pay_config SET payurl='https://api-ncg789.socialgame.xyz',`key`='Nhv9cjGZ6b25w6T2',pub_key='Nhv9cjGZ6b25w6T2' WHERE type='autotopup';

UPDATE transfer_config SET request_url='https://api-ncg789.socialgame.xyz',`key`='Nhv9cjGZ6b25w6T2',pub_key='Nhv9cjGZ6b25w6T2',partner_id='Nhv9cjGZ6b25w6T2' WHERE `code`='AUTOTOPUP';