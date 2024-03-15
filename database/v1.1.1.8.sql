
#游戏活动
INSERT INTO `active_type`(`id`, `name`, `description`, `sort`, `image`, `status`, `created_uid`, `updated_uid`)
VALUES (10, '游戏分类', '游戏分类', 10, '', 'enabled', NULL, NULL);

#游戏模板
INSERT INTO `active_template`(`id`, `name`, `description`, `state`, `created_uid`)
VALUES (10, 'Game Classification', 'Game Classification', '', NULL);

ALTER TABLE `admin_index_third`
MODIFY COLUMN `dml_total` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总打码量' AFTER `recharge_witchdraw`;