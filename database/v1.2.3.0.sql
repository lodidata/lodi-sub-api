

#会员报表
ALTER TABLE `rpt_user`
ADD COLUMN `deposit_user_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '用户充值次数' AFTER `deposit_user_amount`,
ADD COLUMN `withdrawal_user_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '用户取款次数' AFTER `withdrawal_user_amount`;


#IP排行榜
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
VALUES
(335, 146, 'IP排行榜', NULL, NULL, NULL, 1),
(336, 335, '注册ip排行榜查询', 'GET', '/report/iprank/recharge', 1, 1),
(337, 335, '注册ip排行榜导出', 'GET', '/report/iprank/recharge/export', NULL, 1),
(338, 335, '注册ip排行榜详情', 'GET', '/report/iprank/recharge/detail', NULL, 1),
(339, 335, '登录ip排行榜查询', 'GET', '/report/iprank/login', 1, 1),
(340, 335, '登录ip排行榜导出', 'GET', '/report/iprank/login/export', NULL, 1),
(341, 335, '登录ip排行榜详情', 'GET', '/report/iprank/login/detail', NULL, 1);


#更新客服注册人数
select concat("UPDATE kefu_telecom SET register_num=",count(kti.id)," WHERE id=",kt.id,";") from kefu_telecom kt left join kefu_telecom_item kti on kt.id=kti.pid group by kti.pid;