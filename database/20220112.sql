#银行图标
UPDATE dev_game.bank set h5_logo = concat("https://update.a1jul.com/kgb/bank/",code,".png");

#新霸电子打码
ALTER TABLE `user_dml` 
ADD COLUMN `SGMK` int(10) UNSIGNED NULL DEFAULT 0 AFTER `RCB`,
ADD COLUMN `SGMKJJ` int(10) UNSIGNED NULL DEFAULT 0 AFTER `SGMK`,
ADD COLUMN `SGMKTAB` int(10) UNSIGNED NULL DEFAULT 0 AFTER `SGMKJJ`,
ADD COLUMN `SGMKBY` int(10) UNSIGNED NULL DEFAULT 0 AFTER `SGMKTAB`;

#拉单错误日志
ALTER TABLE `game_order_error` 
ADD COLUMN `error` varchar(255) NULL AFTER `json`