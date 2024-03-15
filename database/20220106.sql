ALTER TABLE `dev_game`.`dml`
MODIFY COLUMN `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT ''1待稽核0已稽核'' AFTER `withdraw_bet`;