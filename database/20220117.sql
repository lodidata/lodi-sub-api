#更新竖版图片
UPDATE game_3th SET game_img=REPLACE(game_img,"/game/","/game/vert/");
UPDATE game_3th SET game_img=REPLACE(game_img,"'","");