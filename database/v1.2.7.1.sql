# 添加索引
ALTER TABLE `rebet_log`
ADD INDEX `idx_user_active`(`user_id`, `active_apply_id`);