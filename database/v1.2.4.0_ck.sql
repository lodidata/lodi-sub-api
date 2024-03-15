
#lodi_super_admin

#CG更新字段
ALTER TABLE `game_order_cg` ADD COLUMN `gameCategoryType` varchar(10) NOT NULL DEFAULT 'slot' COMMENT '游戏分类slot,pvp';
update game_order_cg set gameCategoryType='pvp' where GameType in ( select kind_id from game_3th where game_id=92);