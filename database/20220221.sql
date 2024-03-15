#添加增加汇总金额不对记录日志 game_order_check_error

CREATE TABLE `game_order_check_error`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '游戏类型',
  `now` datetime NOT NULL COMMENT '开始查询时间',
  `json` json NULL COMMENT '查询条件',
  `error` json NULL COMMENT '错误数据',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单汇总错误表' ROW_FORMAT = Dynamic;