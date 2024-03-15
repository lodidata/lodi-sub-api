UPDATE `user_level` SET `name` = 'LV1' WHERE `id` = 1;

INSERT INTO level_online (level_id, pay_plat) VALUES
(1,'nn88pay'),
(2,'nn88pay'),
(3,'nn88pay'),
(4,'nn88pay'),
(5,'nn88pay'),
(6,'nn88pay'),
(7,'nn88pay'),
(8,'nn88pay'),
(1,'poppay'),
(2,'poppay'),
(3,'poppay'),
(4,'poppay'),
(5,'poppay'),
(6,'poppay'),
(7,'poppay'),
(8,'poppay');


#清空原支付渠道
TRUNCATE TABLE funds_channel;
INSERT INTO `funds_channel` VALUES (1, 1, 'Bank transfer', 'Bank transfer', 'offline', 0, 1);
INSERT INTO `funds_channel` VALUES (2, 1, '88PAY', '88PAY', 'online', 0, 1);
INSERT INTO `funds_channel` VALUES (11, 2, 'POPPAY', 'POPPAY', 'online', 0, 1);