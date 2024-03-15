#客服代码
INSERT INTO `system_config` (`module`, `name`, `type`, `key`) VALUES ('system', '客服代码', 'string', 'kefu_code');
#是否返佣
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`) VALUES ('rakeBack', ' 是否返佣', 'bool', 'bkge_open', '1', '1开，0关');


#redis
# del system.config.global.key