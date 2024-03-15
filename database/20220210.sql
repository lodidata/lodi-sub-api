#PG游戏正式环境配置
UPDATE `game_api` SET `cagent` = 'AFCAA378-1156-4550-BD07-4659A8C0B976', 
`des_key` = 'DC072F3CBBE34532AC00E0931CAEC865', 
`key` = 'B07F23C3965048C8AA6F5CA40B67C501', 
`loginUrl` = 'https://m.pgjksjk.com', 
`orderUrl` = 'https://api.pg-bo.net/external-datagrabber/', 
`apiUrl` = 'https://api.pg-bo.net/external/' 
 WHERE `id` = 74;

#彩票玩法更新泰文

ALTER TABLE `lottery_play_struct` 
ADD COLUMN `rename` varchar(100) NULL COMMENT '中文名称' AFTER `name`;

UPDATE `lottery_play_struct` set `rename`=`name`;

UPDATE `lottery_play_struct` SET `name` = 'วิ่งล่าง' WHERE `id` = 6;
UPDATE `lottery_play_struct` SET `name` = 'วิ่งบน' WHERE `id` = 4;
UPDATE `lottery_play_struct` SET `name` = '3ตัวบน' WHERE `id` = 1;
UPDATE `lottery_play_struct` SET `name` = '3ตัวโต๊ด' WHERE `id` = 5;
UPDATE `lottery_play_struct` SET `name` = '2ตัวบน' WHERE `id` = 2;
UPDATE `lottery_play_struct` SET `name` = '2ตัวล่าง' WHERE `id` = 3;
UPDATE `lottery_play_struct` SET `group` = '2ตัวล่าง' WHERE `id` = 6;
UPDATE `lottery_play_struct` SET `group` = '2ตัวล่าง' WHERE `id` = 3;
UPDATE `lottery_play_struct` SET `group` = '3ตัวบน' WHERE `id` = 1;
UPDATE `lottery_play_struct` SET `group` = '3ตัวบน' WHERE `id` = 4;
UPDATE `lottery_play_struct` SET `group` = '3ตัวบน' WHERE `id` = 5;
UPDATE `lottery_play_struct` SET `group` = '2ตัวบน' WHERE `id` = 2;

update lottery_play_base_odds set `name`='3ตัวบน' WHERE play_id = 28;
update lottery_play_base_odds set `name`='2ตัวบน' WHERE play_id = 59;
update lottery_play_base_odds set `name`='2ตัวล่าง' WHERE play_id = 63;
update lottery_play_base_odds set `name`='วิ่งบน' WHERE play_id = 72;
update lottery_play_base_odds set `name`='3ตัวโต๊ด' WHERE play_id = 85;
update lottery_play_base_odds set `name`='วิ่งล่าง' WHERE play_id = 86;

update lottery_play_limit_odds set `name`='3ตัวบน' WHERE play_id = 28;
update lottery_play_limit_odds set `name`='2ตัวบน' WHERE play_id = 59;
update lottery_play_limit_odds set `name`='2ตัวล่าง' WHERE play_id = 63;
update lottery_play_limit_odds set `name`='วิ่งบน' WHERE play_id = 72;
update lottery_play_limit_odds set `name`='3ตัวโต๊ด' WHERE play_id = 85;
update lottery_play_limit_odds set `name`='วิ่งล่าง' WHERE play_id = 86;

update lottery_play_odds set `name`='3ตัวบน' WHERE play_id = 28;
update lottery_play_odds set `name`='2ตัวบน' WHERE play_id = 59;
update lottery_play_odds set `name`='2ตัวล่าง' WHERE play_id = 63;
update lottery_play_odds set `name`='วิ่งบน' WHERE play_id = 72;
update lottery_play_odds set `name`='3ตัวโต๊ด' WHERE play_id = 85;
update lottery_play_odds set `name`='วิ่งล่าง' WHERE play_id = 86;
