# 修改规则发放时间类型
ALTER TABLE `active_rule`
MODIFY COLUMN `issue_time` time(0) NULL DEFAULT NULL COMMENT '发放时间(时分秒)';
