ALTER TABLE `rpt_agent`
ADD COLUMN `is_valid_agent` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是有效代理(1：是，0：不是)' AFTER `deposit_user_num`;

ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `new_valid_agent_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增有效代理数' AFTER `new_register_deposit_num`;

ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `agent_first_deposit_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '每日代理带来的新充会员数(首充用户且有上级的人数)' AFTER `new_valid_agent_num`;