CREATE TABLE `transfer_no_sub` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(60) COLLATE utf8_unicode_ci NOT NULL COMMENT '主订单号',
  `sub_order` varchar(60) COLLATE utf8_unicode_ci NOT NULL COMMENT '平台订单号',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '订单金额',
  `currency` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '币种',
  `status` enum('success','fail','waiting','confirming','dispute') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting' COMMENT '订单状态 success=成功,fail=失败,waiting=等待中,confirming=待确认,dispute=争议',
  `order_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '订单类型 1=内充 2=兜底',
  `is_reward` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否奖励 1=是 0=否',
  `reward` int(11) NOT NULL DEFAULT '0' COMMENT '奖励金额',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tr_no_sub_or_sn` (`sub_order`) USING BTREE,
  KEY `tra_no_sub_tra_no` (`transfer_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='内充子订单';