#前端是否显示支付页
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES 
('recharge', 'AutoTopup', 'bool', 'autotopup', '1', '1开0关', 'enabled'),
('recharge', 'QRCode', 'bool', 'qrcode', '1', '1开0关', 'enabled'),
('recharge', '线下入款', 'bool', 'offline', '1', '1开0关', 'enabled');

#redis del system.config.global.key