#开启代理时间
ALTER TABLE `user`
ADD COLUMN `agent_time` timestamp NULL DEFAULT NULL COMMENT '开启代理时间' AFTER `state`;
