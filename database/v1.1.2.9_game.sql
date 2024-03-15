#+++++++++++++++++lodi超管
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`,`status`, `switch`, `across_status`) VALUES (110, 17, 'JILIQP', 'JILIQP', 'JILI', 'JILI棋牌', 'enabled', 'enabled', 'enabled');

INSERT INTO game_3th ( kind_id, game_id, game_name, `rename`, alias, type, sort )
VALUES
( 72, 110, 'TeenPatti', 'TeenPatti', 'TeenPatti', 'QP', 128 ),
( 75, 110, 'AK47', 'AK47', 'AK47', 'QP', 125 ),
( 79, 110, 'Andar Bahar', 'Andar Bahar', 'AndarBahar', 'QP', 121),
( 94, 110, 'Rummy', 'Rummy', 'Rummy', 'QP', 106 ),
( 127, 110, 'Callbreak', 'Callbreak', 'Callbreak', 'QP', 73 ),
( 159, 110, 'TeenPatti Joker', 'TeenPatti Joker', 'TeenPattiJoker', 'QP', 41 ),
( 160, 110, 'Callbreak Quick', 'Callbreak Quick', 'CallbreakQuick', 'QP', 40 ),
( 161, 110, 'TeenPatti 20-20', 'TeenPatti 20-20', 'TeenPatti20-20', 'QP', 39 );


#+++++++++++++++++++lodi子站

ALTER TABLE `user_dml` ADD COLUMN `JILIQP` int(10) UNSIGNED NOT NULL DEFAULT 0;


#JILI添加真人游戏

INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`,`status`, `switch`, `across_status`) VALUES (110, 17, 'JILIQP', 'JILIQP', 'JILI', 'JILI棋牌', 'enabled', 'enabled', 'enabled');

INSERT INTO game_3th ( kind_id, game_id, game_name, `rename`, alias, type, sort, game_img )
VALUES
( 72, 110, 'TeenPatti', 'TeenPatti', 'TeenPatti', 'QP', 128, 'https://update.lodigame.com/lodi/game/vert/jili/72.png' ),
( 75, 110, 'AK47', 'AK47', 'AK47', 'QP', 125, 'https://update.lodigame.com/lodi/game/vert/jili/75.png' ),
( 79, 110, 'Andar Bahar', 'Andar Bahar', 'AndarBahar', 'QP', 121, 'https://update.lodigame.com/lodi/game/vert/jili/79.png' ),
( 94, 110, 'Rummy', 'Rummy', 'Rummy', 'QP', 106, 'https://update.lodigame.com/lodi/game/vert/jili/94.png' ),
( 127, 110, 'Callbreak', 'Callbreak', 'Callbreak', 'QP', 73, 'https://update.lodigame.com/lodi/game/vert/jili/127.png' ),
( 159, 110, 'TeenPatti Joker', 'TeenPatti Joker', 'TeenPattiJoker', 'QP', 41, 'https://update.lodigame.com/lodi/game/vert/jili/159.png' ),
( 160, 110, 'Callbreak Quick', 'Callbreak Quick', 'CallbreakQuick', 'QP', 40, 'https://update.lodigame.com/lodi/game/vert/jili/160.png' ),
( 161, 110, 'TeenPatti 20-20', 'TeenPatti 20-20', 'TeenPatti20-20', 'QP', 39, 'https://update.lodigame.com/lodi/game/vert/jili/161.png' );

#+++++++++++redis lodi子站

del menu:vertical:list
