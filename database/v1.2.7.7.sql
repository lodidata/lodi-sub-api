#日返流水倍数
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '日返流水倍数', 'json', 'day', '{}', NULL, 'enabled');

#周返流水倍数
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '周返流水倍数', 'int', 'week', '0', NULL, 'enabled');

#月返流水倍数
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '月返流水倍数', 'int', 'month', '0', NULL, 'enabled');