#代理申请优化
alter table agent_apply add `reply` text COMMENT '回复用户' after `remark`;

#活动回水设置
ALTER TABLE `active_rule` ADD COLUMN `issue_day` tinyint NOT NULL DEFAULT 0 COMMENT '发放天，例如一周的星期几，一月的第几号' AFTER `issue_time`;

#回水记录
ALTER TABLE `funds_deal_log` MODIFY COLUMN `deal_type` smallint(6) UNSIGNED NOT NULL DEFAULT 1 COMMENT '交易类型:101线上入款，102公司入款，103体育派彩，104彩票派彩，105优惠活动，106手动存款，107返水优惠，108代理退佣，109销售返点，110彩票撤单，111存入代理，112手动增加余额，113手动发放返水，114手动发放优惠，115提款解冻，116追号解冻，117体育撤单, 201会员提款，202彩票下注，203体育下注，204手动提款，205扣除优惠，206代理提款，207手动减少余额，208提款冻结，209追号冻结，210提现扣款，301子转主钱包，302主转子钱包，303手动子转主钱包，304手动主转子钱包，501视讯下注，502视讯派彩，503视讯撤单, 601撤销已经发放的代理退佣,701-日回水，702-周回水，703-月回水' AFTER `order_number`;

#盈亏返佣新公式
ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;

ALTER TABLE `agent_loseearn_week_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_week_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;

ALTER TABLE `agent_loseearn_month_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_month_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;


#月返28号
UPDATE `active_rule` SET `issue_day`='28',`issue_time`='12:00:00' WHERE template_id=9;