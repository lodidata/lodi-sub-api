#上级返佣0.6%，上上级返佣返佣0.06%
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `state`) 
VALUES 
('rakeBack', '上级返佣', 'string', 'bkge1', '0.6', 'enabled'),
('rakeBack', '上上级返佣', 'string', 'bkge2', '0.06', 'enabled');

UPDATE system_config set state = 'disabled' WHERE module='rakeBack' and id<89 and id<>42;

#更新SQL后删除redis 

#del system.config.global.key