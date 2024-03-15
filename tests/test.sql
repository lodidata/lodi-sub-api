
--  试玩用户需要清理表
SELECT count(*) from user;
SELECT count(*) from send_prize;
SELECT count(*) from lottery_order;
select count(*) from lottery_chase;
select count(*) from funds_deposit;
select count(*) from funds;
select count(*) from funds_child;
select count(*) from funds_deal_log;
select count(*) from funds_deal_manual;
select count(*) from message;
select count(*) from message_pub;
select count(*) from crease_money;
select count(*) from user_rake_log;

--  30天转移数据历史表
SELECT count(*) from send_prize;
SELECT count(*) from lottery_order;
select count(*) from lottery_chase;
select count(*) from funds_deposit;
select count(*) from funds_deal_log;
select count(*) from funds_deal_manual;
select count(*) from message;
select count(*) from message_pub;
select count(*) from crease_money;
select count(*) from user_rake_log;
select count(*) from transfer_order;

-- 清理
select count(*) from transfer_log;