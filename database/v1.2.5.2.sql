
#增加分类
select concat("INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `img`, `sort`, `status`, `switch`) VALUES (155, 24, 'IG', 'IG', 'IG', 'IG斗鸡', '",img,"', 2, 'enabled',  'enabled');") as rpt from game_menu where id=102;

#转移游戏
update game_3th set game_id=155 where game_id=102 and kind_id=4510;