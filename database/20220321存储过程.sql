/*
 Navicat Premium Data Transfer

 Source Server         : 52.74.208.242
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : 52.74.208.242:3308
 Source Schema         : dev_game

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 21/03/2022 17:46:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Procedure structure for p_rpt_agent
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_agent`;
delimiter ;;
CREATE PROCEDURE `p_rpt_agent`(IN `v_begin_date` varchar(15))
BEGIN

  DECLARE now_date date;
	DECLARE v_end_date date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_agent') THEN
		INSERT INTO rpt_exec_his (tab_name, exec_date)
			SELECT 'rpt_agent', DATE(MIN(count_date)) FROM rpt_user;
	END IF;

	IF v_begin_date = '' THEN
		SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_agent';
	END IF;

  set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));

	WHILE v_begin_date <= now_date DO

		SET v_end_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
		SET v_start_time = CONCAT(v_begin_date, ' 00:00:00');
		SET v_end_time = CONCAT(v_end_date, ' 00:00:00');
		SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_agent
  (count_date,
   agent_id,
   agent_name,
   agent_real_name,
   agent_cnt,
   agent_inc_cnt,
   first_deposit_cnt,
   deposit_agent_amount,
   withdrawal_agent_amount,
   bet_agent_amount,
   prize_agent_amount,
   coupon_agent_amount,
   return_agent_amount,
   turn_card_agent_winnings,
   promotion_agent_winnings,
   back_agent_amount,
	 new_register_deposit_amount,
	 new_register_deposit_num,
	 deposit_user_num,
   register_time,
	 balance_amount)
  SELECT *
    FROM (SELECT t.*, m.balance_amount
            FROM (SELECT "', v_begin_date, '" count_date,
                         ua.user_id agent_id,
                         u.`name` agent_name,
                         IFNULL(p.`name`, "") agent_real_name,
                         IFNULL((SELECT COUNT(*)
														 FROM child_agent ca1
														 INNER JOIN user u1
																	ON u1.id = ca1.cid
																		WHERE ca1.pid = ua.user_id
																		AND u1.tags != 4),0) agent_cnt,
                         IFNULL((SELECT COUNT(*)
                                  FROM child_agent ca2
															    INNER JOIN user u2 ON u2.id = ca2.cid
																			WHERE ca2.pid = ua.user_id
																		AND u2.tags != 4
                                    AND ca2.update_time >= "', v_start_time, '"
                                    AND ca2.update_time < "', v_end_time, '"),0) agent_inc_cnt,
                         IFNULL(SUM(IFNULL(t1.first_recharge_status, 0)), 0) first_deposit_cnt,
                         IFNULL(SUM(IFNULL(ru.deposit_user_amount, 0)), 0) deposit_agent_amount,
                         IFNULL(SUM(IFNULL(ru.withdrawal_user_amount, 0)), 0) withdrawal_agent_amount,
                         IFNULL(SUM(IFNULL(ru.bet_user_amount, 0)), 0) bet_agent_amount,
                         IFNULL(SUM(IFNULL(ru.prize_user_amount, 0)), 0) prize_agent_amount,
                         IFNULL(SUM(IFNULL(ru.coupon_user_amount, 0)), 0) coupon_agent_amount,
                         IFNULL(SUM(IFNULL(ru.return_user_amount, 0)), 0) return_agent_amount,
                         IFNULL(SUM(IFNULL(ru.turn_card_user_winnings, 0)), 0) turn_card_agent_winnings,
                         IFNULL(SUM(IFNULL(ru.promotion_user_winnings, 0)), 0) promotion_agent_winnings,
                         IFNULL(SUM(IFNULL(ru.back_user_amount, 0)), 0) back_agent_amount,
												 IFNULL(SUM(IF(t1.first_recharge_status,ru.deposit_user_amount,0)), 0) new_register_deposit_amount,
												 IFNULL(SUM(IFNULL(t1.first_recharge_status, 0)), 0) new_register_deposit_num,
												 IFNULL(SUM(IF(ru.deposit_user_amount > 0,1,0)), 0) deposit_user_num,
                         u.created register_time
                    FROM user_agent ua
                   INNER JOIN `user` u
                      ON ua.user_id = u.id
                   INNER JOIN (select pid, cid from child_agent
	        				             union
	        									   select id pid, id cid from user)	ca
                      ON ua.user_id = ca.pid
                   INNER JOIN rpt_user ru
                      ON ru.user_id = ca.cid
                      AND ru.count_date = "', v_begin_date, '"
                    LEFT JOIN profile p
                      ON p.user_id = ua.user_id
                    LEFT JOIN (
																	SELECT id, "1" first_recharge_status
                                FROM `user`
                               WHERE first_recharge_time >= "', v_start_time, '"
                                 AND first_recharge_time < "', v_end_time, '"
																AND tags != 4
													) t1
                      ON t1.id = ca.cid
	        	   WHERE tags != 4
                   GROUP BY ua.user_id) t
	        LEFT JOIN (SELECT ca.pid,
												SUM(balance / 100) balance_amount
											FROM funds f
											INNER JOIN user u ON u.wallet_id = f.id
											INNER JOIN (SELECT pid, cid FROM child_agent
																	UNION
																	SELECT id pid, id cid FROM user) ca ON u.id = ca.cid
												WHERE tags != 4
												GROUP BY ca.pid) m
						ON m.pid = t.agent_id) a
      ON DUPLICATE KEY UPDATE count_date = a.count_date,
   agent_id = a.agent_id,
   agent_name = a.agent_name,
   agent_real_name = a.agent_real_name,
   agent_cnt = a.agent_cnt,
   agent_inc_cnt = a.agent_inc_cnt,
   first_deposit_cnt = a.first_deposit_cnt,
   deposit_agent_amount = a.deposit_agent_amount,
   withdrawal_agent_amount = a.withdrawal_agent_amount,
   bet_agent_amount = a.bet_agent_amount,
   prize_agent_amount = a.prize_agent_amount,
   coupon_agent_amount = a.coupon_agent_amount,
   return_agent_amount = a.return_agent_amount,
   turn_card_agent_winnings = a.turn_card_agent_winnings,
   promotion_agent_winnings = a.promotion_agent_winnings,
   back_agent_amount = a.back_agent_amount,
	 new_register_deposit_amount = a.new_register_deposit_amount,
	 new_register_deposit_num = a.new_register_deposit_num,
	 deposit_user_num = a.deposit_user_num,
   register_time = a.register_time,
	 balance_amount = a.balance_amount;');
        SELECT @sqlstr_01;
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;

		SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
	END WHILE;

	UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_agent';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_deposit_withdrawal_day
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_deposit_withdrawal_day`;
delimiter ;;
CREATE PROCEDURE `p_rpt_deposit_withdrawal_day`(IN `v_begin_date` varchar(15))
BEGIN

  DECLARE now_date date;
	DECLARE v_yesterday date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_deposit_withdrawal_day') THEN
		INSERT INTO rpt_exec_his (tab_name, exec_date)
			SELECT 'rpt_deposit_withdrawal_day', DATE(MIN(created)) FROM funds_deal_log;
	END IF;

	IF v_begin_date = '' THEN
		SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_deposit_withdrawal_day';
	END IF;

	set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));

	WHILE v_begin_date <= now_date DO

		SET v_yesterday = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
		SET v_start_time = CONCAT(v_yesterday, ' 20:00:00');
		SET v_end_time = CONCAT(v_begin_date, ' 20:00:00');
		SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_deposit_withdrawal_day
  (count_date,
   offline_amount,
   offline_cnt,
   online_amount,
   online_cnt,
   manual_deposit_amount,
   manual_deposit_cnt,
   income_amount,
   withdrawal_amount,
   withdrawal_cnt,
   coupon_amount,
   coupon_cnt,
   return_amount,
   return_cnt,
   manual_deduction_amount,
   manual_deduction_cnt,
   out_amount,
   turn_card_winnings,
   turn_card_winnings_cnt,
   promotion_winnings,
   promotion_winnings_cnt,
   game_code_amount,
   game_code_cnt,
   back_amount,
   back_cnt)
  SELECT *
    FROM (SELECT t.count_date,
								 0 offline_amount,
                 0 offline_cnt,
                 0 online_amount,
                 0 online_cnt,
                 0 manual_deposit_amount,
                 0 manual_deposit_cnt,
                 0 income_amount,
                 IFNULL(fdl.withdrawal_amount, 0) withdrawal_amount,
                 IFNULL(fdl.withdrawal_cnt, 0) withdrawal_cnt,
                 IFNULL(fdl.coupon_amount, 0) coupon_amount,
                 IFNULL(fdl.coupon_cnt, 0) coupon_cnt,
                 IFNULL(fdl.return_amount, 0) return_amount,
                 IFNULL(fdl.return_cnt, 0) return_cnt,
                 IFNULL(fdl.manual_deduction_amount, 0) manual_deduction_amount,
                 IFNULL(fdl.manual_deduction_cnt, 0) manual_deduction_cnt,
                 IFNULL((IFNULL(fdl.out_amount, 0) + IFNULL(b.back_amount, 0)), 0) out_amount,
                 IFNULL(fdl.turn_card_winnings, 0) turn_card_winnings,
                 IFNULL(fdl.turn_card_winnings_cnt, 0) turn_card_winnings_cnt,
                 IFNULL(fdl.promotion_winnings, 0) promotion_winnings,
                 IFNULL(fdl.promotion_winnings_cnt, 0) promotion_winnings_cnt,
                 IFNULL(fdl.game_code_amount, 0) game_code_amount,
                 IFNULL(fdl.game_code_cnt, 0) game_code_cnt,
                 IFNULL(b.back_amount, 0) back_amount,
                 IFNULL(b.back_cnt, 0) back_cnt
            FROM (SELECT "', v_begin_date, '" count_date
                    FROM funds_deal_log
                   WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309, 501)
				     AND created >= "', v_start_time, '"
                     AND created < "', v_end_time, '"
                  UNION
                  SELECT "', v_begin_date, '" count_date
                    FROM bkge url
                   WHERE created >= "', v_start_time, '"
                     AND created < "', v_end_time, '") t
            LEFT JOIN (SELECT "', v_begin_date, '" count_date,
                              SUM(CASE WHEN fdl1.deal_type in (101, 102, 106) THEN fdl1.deal_money ELSE 0 END) / 100 income_amount,
                              SUM(CASE WHEN fdl1.deal_type in (201) THEN fdl1.deal_money ELSE 0 END) / 100 withdrawal_amount,
                              SUM(CASE WHEN fdl1.deal_type in (201) THEN 1 ELSE 0 END) withdrawal_cnt,
                              SUM(CASE WHEN fdl1.deal_type in (105, 114) THEN fdl1.deal_money ELSE 0 END) / 100 coupon_amount,
                              SUM(CASE WHEN fdl1.deal_type in (105, 114) THEN 1 ELSE 0 END) coupon_cnt,
                              SUM(CASE WHEN fdl1.deal_type in (107, 109, 113) THEN fdl1.deal_money ELSE 0 END) / 100 return_amount,
                              SUM(CASE WHEN fdl1.deal_type in (107, 109, 113) THEN 1 ELSE 0 END) return_cnt,
                              SUM(CASE WHEN fdl1.deal_type in (204) THEN fdl1.deal_money ELSE 0 END) / 100 manual_deduction_amount,
                              SUM(CASE WHEN fdl1.deal_type in (204) THEN 1 ELSE 0 END) manual_deduction_cnt,
                              SUM(CASE WHEN fdl1.deal_type in (201, 105, 114, 107, 109, 113, 204) THEN fdl1.deal_money ELSE 0 END) / 100 out_amount,
		                      SUM(CASE WHEN fdl1.deal_type in (309) THEN fdl1.deal_money / 100 ELSE 0 END) turn_card_winnings,
		                      SUM(CASE WHEN fdl1.deal_type in (309) THEN 1 ELSE 0 END) turn_card_winnings_cnt,
		                      SUM(CASE WHEN fdl1.deal_type in (308) THEN fdl1.deal_money ELSE 0 END) / 100 promotion_winnings,
		                      SUM(CASE WHEN fdl1.deal_type in (308) THEN 1 ELSE 0 END) promotion_winnings_cnt,
		                      SUM(CASE WHEN fdl1.deal_type in (501) THEN fdl1.deal_money ELSE 0 END) / 100 game_code_amount,
		                      SUM(CASE WHEN fdl1.deal_type in (501) THEN 1 ELSE 0 END) game_code_cnt
                        FROM funds_deal_log fdl1
					   INNER JOIN user u ON u.id = fdl1.user_id
                       WHERE fdl1.deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309, 501)
					     AND u.tags != 4
					     AND fdl1.created >= "', v_start_time, '"
                         AND fdl1.created < "', v_end_time, '") fdl
              ON fdl.count_date = t.count_date
            LEFT JOIN (SELECT "', v_begin_date, '" count_date,
                             SUM(b.bkge) / 100 back_amount,
                             COUNT(*) back_cnt
                        FROM bkge b
					   INNER JOIN user u ON u.id = b.user_id
                       WHERE u.tags != 4
					     AND b.created >= "', v_start_time, '"
                         AND b.created < "', v_end_time, '") b
              ON b.count_date = t.count_date) a
      ON DUPLICATE KEY UPDATE count_date = a.count_date,
   offline_amount = a.offline_amount,
   offline_cnt = a.offline_cnt,
   online_amount = a.online_amount,
   online_cnt = a.online_cnt,
   manual_deposit_amount = a.manual_deposit_amount,
   manual_deposit_cnt = a.manual_deposit_cnt,
   income_amount = a.income_amount,
   withdrawal_amount = a.withdrawal_amount,
   withdrawal_cnt = a.withdrawal_cnt,
   coupon_amount = a.coupon_amount,
   coupon_cnt = a.coupon_cnt,
   return_amount = a.return_amount,
   return_cnt = a.return_cnt,
   manual_deduction_amount = a.manual_deduction_amount,
   manual_deduction_cnt = a.manual_deduction_cnt,
   out_amount = a.out_amount,
   turn_card_winnings = a.turn_card_winnings,
   turn_card_winnings_cnt = a.turn_card_winnings_cnt,
   promotion_winnings = a.promotion_winnings,
   promotion_winnings_cnt = a.promotion_winnings_cnt,
   game_code_amount = a.game_code_amount,
   game_code_cnt = a.game_code_cnt,
   back_amount = a.back_amount,
   back_cnt = a.back_cnt;');
       SELECT @sqlstr_01;
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;

		SET @sqlstr_02 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day
			SET new_user_num = (SELECT COUNT(1) FROM `user`
						 WHERE created >= "', v_start_time, '"
						 AND created < "', v_end_time, '")
			WHERE count_date = "', v_begin_date, '"
						 ;');
				SELECT @sqlstr_02;
		PREPARE sqlstr_02 FROM @sqlstr_02;
		EXECUTE sqlstr_02;
		DEALLOCATE PREPARE sqlstr_02;

		SET @sqlstr_03 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(select  "', v_begin_date, '" count_date, ifnull(count(DISTINCT user_id),0) deposit_user_num,ifnull(sum(if(FIND_IN_SET("new",state), 1, 0)),0) new_deposit_user_num ,ifnull(sum(if(FIND_IN_SET("offline",state), money, 0))/100,0)  offline_amount , ifnull(sum(if(FIND_IN_SET("offline",state), 1, 0)),0) offline_cnt,ifnull(sum(if(FIND_IN_SET("online",state), money, 0))/100,0) online_amount,ifnull(sum(if(FIND_IN_SET("online",state), 1, 0)),0)  online_cnt, ifnull(sum(if(FIND_IN_SET("tz",state), money, 0))/100,0) manual_deposit_amount ,ifnull(sum(if(FIND_IN_SET("tz",state), 1, 0)),0) manual_deposit_cnt
				FROM funds_deposit
			WHERE created >= "', v_start_time, '"
				AND created <  "', v_end_time, '"
				AND  FIND_IN_SET("paid",status)) r
		ON rdwd.count_date = r.count_date
		SET rdwd.deposit_user_num = r.deposit_user_num,
				rdwd.new_deposit_user_num = r.new_deposit_user_num,
				rdwd.offline_amount = r.offline_amount,
				rdwd.offline_cnt = r.offline_cnt,
				rdwd.online_amount = r.online_amount,
				rdwd.online_cnt = r.online_cnt,
				rdwd.manual_deposit_amount = r.manual_deposit_amount,
				rdwd.manual_deposit_cnt = r.manual_deposit_cnt,
				rdwd.income_amount = r.offline_amount + r.online_amount + r.manual_deposit_amount
					;');
						 SELECT @sqlstr_03;
		PREPARE sqlstr_03 FROM @sqlstr_03;
		EXECUTE sqlstr_03;
		DEALLOCATE PREPARE sqlstr_03;

				SET @sqlstr_04 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(select  "', v_begin_date, '" count_date, ifnull(count(DISTINCT user_id),0) new_register_deposit_num,ifnull(sum(money),0) /100 new_register_deposit_amount
				FROM funds_deposit
			WHERE created >= "', v_start_time, '"
				AND created <  "', v_end_time, '"
				AND  FIND_IN_SET("paid",status)
				AND user_id in(SELECT id FROM `user`
						 WHERE first_recharge_time >= "', v_start_time, '"
						 AND first_recharge_time < "', v_end_time, '")) r
		ON rdwd.count_date = r.count_date
		SET rdwd.new_register_deposit_num = r.new_register_deposit_num,
				rdwd.new_register_deposit_amount = r.new_register_deposit_amount
						 ;');
						 SELECT @sqlstr_04;
		PREPARE sqlstr_04 FROM @sqlstr_04;
		EXECUTE sqlstr_04;
		DEALLOCATE PREPARE sqlstr_04;

		SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
	END WHILE;

	UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_deposit_withdrawal_day';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_lottery_earnlose
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_lottery_earnlose`;
delimiter ;;
CREATE PROCEDURE `p_rpt_lottery_earnlose`(IN `v_begin_date` varchar(15))
BEGIN

  DECLARE now_date date;
	DECLARE v_yesterday date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_lottery_earnlose') THEN
		INSERT INTO rpt_exec_his(tab_name,exec_date)
			SELECT 'rpt_lottery_earnlose', DATE(MIN(created)) FROM send_prize ;
	END IF;

	IF v_begin_date = '' THEN
		SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_lottery_earnlose';
	END IF;

	set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));

	WHILE v_begin_date <= now_date DO

		SET v_yesterday = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
		SET v_start_time = CONCAT(v_yesterday, ' 20:00:00');
		SET v_end_time = CONCAT(v_begin_date, ' 20:00:00');

		DELETE FROM rpt_userlottery_earnlose WHERE count_date >= v_begin_date;

		SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_userlottery_earnlose
  SELECT "',v_begin_date,'" count_date,
         b.lottery_id,
         a.user_id,
         COUNT(*) bet_num,
         SUM(a.pay_money) bet_money,
         SUM(a.money) send_money,
         SUM(a.lose_earn) lose_earn,
         NOW()
    FROM send_prize a
   INNER JOIN lottery_order b
      ON a.order_number = b.order_number
     AND a.user_id = b.user_id
   INNER JOIN `user` c
      ON a.user_id = c.id
   WHERE c.tags NOT IN (4, 7)
     AND a.created >= "', v_start_time, '"
     AND a.created < "', v_end_time, '"
   GROUP BY a.user_id, b.lottery_id;');
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;

		DELETE FROM rpt_lottery_earnlose WHERE count_date >= v_begin_date;

		SET @sqlstr_02 = CONCAT(
'INSERT INTO rpt_lottery_earnlose
  SELECT "',v_begin_date,'" count_date,
         t1.lottery_id,
         COUNT(*) bet_num,
         SUM(t2.pay_money) bet_money,
         SUM(t2.money) send_money,
         SUM(t2.lose_earn) total_earnlose,
         NOW() create_time
    FROM lottery_order t1
   INNER JOIN send_prize t2
      ON t1.order_number = t2.order_number
   INNER JOIN lottery t3
      ON t1.lottery_id = t3.id
   INNER JOIN `user` t4
      ON t1.user_id = t4.id
   WHERE t2.created >= "', v_start_time, '"
     AND t2.created < "', v_end_time, '"
     AND t4.tags NOT IN (4, 7)
   GROUP BY  t1.lottery_id;');
		PREPARE sqlstr_02 FROM @sqlstr_02;
		EXECUTE sqlstr_02;
		DEALLOCATE PREPARE sqlstr_02;

		SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
	END WHILE;

	UPDATE rpt_exec_his SET exec_date=DATE_ADD(v_begin_date,INTERVAL -2 DAY) WHERE tab_name = 'rpt_userlottery_earnlose';
		UPDATE rpt_exec_his SET exec_date=DATE_ADD(v_begin_date,INTERVAL -2 DAY) WHERE tab_name = 'rpt_lottery_earnlose';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_order_amount
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_order_amount`;
delimiter ;;
CREATE PROCEDURE `p_rpt_order_amount`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE now_date date;
  DECLARE v_yesterday date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

  IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_order_amount') THEN
    INSERT INTO rpt_exec_his(tab_name, exec_date)
      SELECT 'rpt_order_amount',  DATE(MIN(created)) FROM orders;
  END IF;
  IF v_begin_date = '' THEN
    SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_order_amount';
  END IF;

	set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));
  WHILE v_begin_date <= now_date DO

		SET v_yesterday = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
		SET v_start_time = CONCAT(v_yesterday, ' 20:00:00');
		SET v_end_time = CONCAT(v_begin_date, ' 20:00:00');

    SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_order_amount
  (count_date,
   game_type,
   game_name,
   game_order_cnt,
   game_bet_amount,
   game_prize_amount,
   game_code_amount)
  SELECT *
    FROM (SELECT t.count_date,
                 t.game_type,
                 o.game_name,
                 IFNULL(o.game_order_cnt, 0) game_order_cnt,
                 IFNULL(o.game_bet_amount, 0) game_bet_amount,
                 IFNULL(o.game_prize_amount, 0) game_prize_amount,
                 IFNULL(o.game_code_amount, 0) game_code_amount
            FROM (SELECT "', v_begin_date, '" count_date, game_type
                    FROM orders
                   WHERE `order_time` >= "', v_start_time, '"
									 AND `order_time` < "', v_end_time, '" ) t
           INNER JOIN (SELECT o1.game_type,
                             o1.type_name game_name,
                             COUNT(*) game_order_cnt,
                             SUM(o1.bet) / 100 game_bet_amount,
                             SUM(o1.send_money) / 100 game_prize_amount,
                             SUM(o1.dml) / 100 game_code_amount
                        FROM orders o1
             INNER JOIN user u ON u.id = o1.user_id
                       WHERE u.tags != 4
               AND o1.`order_time` >= "', v_start_time, '"
							 AND o1.`order_time` < "', v_end_time, '"
                       GROUP BY o1.game_type) o
              ON o.game_type = t.game_type) a
      ON DUPLICATE KEY UPDATE count_date = a.count_date,
   game_type = a.game_type,
   game_name = a.game_name,
   game_order_cnt = a.game_order_cnt,
   game_bet_amount = a.game_bet_amount,
   game_prize_amount = a.game_prize_amount,
   game_code_amount = a.game_code_amount;');
    SELECT @sqlstr_01;
  PREPARE sqlstr_01 FROM @sqlstr_01;
  EXECUTE sqlstr_01;
  DEALLOCATE PREPARE sqlstr_01;

UPDATE quota
   SET use_quota =
       (SELECT SUM(game_bet_amount - game_prize_amount)
          FROM rpt_order_amount
         WHERE clear_status = '0'
				   AND game_type != 'ZYCPSTA'),
       surplus_quota = CASE WHEN (SELECT SUM(game_bet_amount - game_prize_amount)
                                    FROM rpt_order_amount
                                   WHERE clear_status = '0'
												             AND game_type NOT IN ('ZYCPSTA', 'ZYCPCHAT')) >= 0
										   THEN total_quota -
                       (SELECT SUM(game_bet_amount - game_prize_amount)
                          FROM rpt_order_amount
                         WHERE clear_status = '0'
												   AND game_type NOT IN ('ZYCPSTA', 'ZYCPCHAT')) ELSE total_quota END;

    SET  v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
  END WHILE;

  UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_order_amount';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_order_num
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_order_num`;
delimiter ;;
CREATE PROCEDURE `p_rpt_order_num`()
BEGIN

	IF NOT EXISTS (SELECT 1
                   FROM (SELECT lottery_number
                           FROM lottery_order
                          WHERE lottery_id = 52
                            AND play_id = 510
                            AND FIND_IN_SET("open", state)
													GROUP BY lottery_number
                          ORDER BY lottery_number DESC LIMIT 2) a
                  INNER JOIN (SELECT MAX(lottery_number) max_lottery_number
                                FROM rpt_order_num) b
                     ON b.max_lottery_number = a.lottery_number) THEN
		SET @sqlstr_02 = CONCAT('
        INSERT INTO rpt_order_num
          (lottery_id,
          lottery_number,
          lottery_num,
          lottery_bet_amount)
          SELECT *
            FROM (SELECT lo.lottery_id,
                         lo.lottery_number,
                         SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT("|", lo.play_number), "|", rht.help_topic_id + 1), "|", -1) lottery_num,
                         SUM(lo.one_money / 100) lottery_bet_amount
                    FROM lottery_order lo
									 INNER JOIN user u on u.id = lo.user_id
                   INNER JOIN rpt_help_topic rht
                      ON rht.help_topic_id < (LENGTH(CONCAT("|", lo.play_number)) - LENGTH(REPLACE(CONCAT("|", lo.play_number), "|", "")) + 1)
                   where lottery_id = 52
                     AND play_id = 510
										 AND u.tags != 4
                   GROUP BY lo.lottery_id, lo.lottery_number, lottery_num) t
              ON DUPLICATE KEY UPDATE lottery_number = t.lottery_number,
                                      lottery_id = t.lottery_id,
                                      lottery_num = t.lottery_num,
                                      lottery_bet_amount = t.lottery_bet_amount;');
        PREPARE sqlstr_02 FROM @sqlstr_02;
        EXECUTE sqlstr_02;
        DEALLOCATE PREPARE sqlstr_02;
	ELSE
		SET @sqlstr_01 = CONCAT('
        INSERT INTO rpt_order_num
          (lottery_id,
          lottery_number,
          lottery_num,
          lottery_bet_amount)
          SELECT *
            FROM (SELECT lo.lottery_id,
                         lo.lottery_number,
												 SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT("|", lo.play_number), "|", rht.help_topic_id + 1), "|", -1) lottery_num,
                         SUM(lo.one_money / 100) lottery_bet_amount
                    FROM lottery_order lo
									 INNER JOIN user u on u.id = lo.user_id
                   INNER JOIN rpt_help_topic rht
										  ON rht.help_topic_id < (LENGTH(CONCAT("|", lo.play_number)) - LENGTH(REPLACE(CONCAT("|", lo.play_number), "|", "")) + 1)
                   inner join (SELECT lottery_number
                                FROM lottery_order
                               WHERE lottery_id = 52
                                 AND play_id = 510
                               GROUP BY lottery_number
                               ORDER BY lottery_number DESC LIMIT 2) a
                      on a.lottery_number = lo.lottery_number
                   where lottery_id = 52
                     AND play_id = 510
										 AND u.tags != 4
                   GROUP BY lo.lottery_id, lo.lottery_number, lottery_num) t
              ON DUPLICATE KEY UPDATE lottery_number = t.lottery_number,
                                      lottery_id = t.lottery_id,
                                      lottery_num = t.lottery_num,
                                      lottery_bet_amount = t.lottery_bet_amount;');
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;
	END IF;

END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_order_user
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_order_user`;
delimiter ;;
CREATE PROCEDURE `p_rpt_order_user`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE now_date date;
  DECLARE v_yesterday date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

  IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_order_user') THEN
    INSERT INTO rpt_exec_his (tab_name, exec_date)
      SELECT 'rpt_order_user', DATE(MIN(created)) FROM orders;
  END IF;

  IF v_begin_date = '' THEN
    SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_order_user';
  END IF;

	set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));
  WHILE v_begin_date <= now_date DO

    SET v_yesterday = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
		SET v_start_time = CONCAT(v_yesterday, ' 20:00:00');
		SET v_end_time = CONCAT(v_begin_date, ' 20:00:00');
    SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_order_user
  (count_date,
   game_type,
   user_id)
  SELECT *
    FROM (SELECT "', v_begin_date, '" count_date,
                 o.game_type,
                 o.user_id
            FROM orders o
       INNER JOIN user u ON u.id = o.user_id
           WHERE u.tags != 4
         AND o.`order_time` >= "', v_start_time, '"
				 AND o.`order_time` < "', v_end_time, '"
           GROUP BY o.game_type, o.user_id) t
      ON DUPLICATE KEY UPDATE count_date = t.count_date,
   game_type = t.game_type,
   user_id = t.user_id;');
        SELECT @sqlstr_01;
    PREPARE sqlstr_01 FROM @sqlstr_01;
    EXECUTE sqlstr_01;
    DEALLOCATE PREPARE sqlstr_01;

    SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
  END WHILE;

  UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_order_user';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_user
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_user`;
delimiter ;;
CREATE PROCEDURE `p_rpt_user`(IN `v_begin_date` varchar(15))
BEGIN

	DECLARE now_date date;
	DECLARE v_end_date date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_user') THEN
		INSERT INTO rpt_exec_his (tab_name, exec_date)
			SELECT 'rpt_user', DATE(MIN(created)) FROM funds_deal_log;
	END IF;

	IF v_begin_date = '' THEN
		SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_user';
	END IF;

	set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));

	WHILE v_begin_date <= now_date DO

		SET v_end_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
		SET v_start_time = CONCAT(v_begin_date, ' 00:00:00');
		SET v_end_time = CONCAT(v_end_date, ' 00:00:00');
		SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_user
  (count_date,
   user_id,
   user_name,
   real_name,
   superior_id,
   deposit_user_amount,
   withdrawal_user_amount,
   bet_user_amount,
   prize_user_amount,
   coupon_user_amount,
   return_user_amount,
   turn_card_user_winnings,
   promotion_user_winnings,
	 first_deposit,
   back_user_amount,
   register_time)
  SELECT *
    FROM (SELECT t.count_date,
	             t.user_id,
                 u.`name` user_name,
                 p.`name` real_name,
                 ua.uid_agent superior_id,
                 IFNULL(fdl.deposit_user_amount, 0) deposit_user_amount,
                 IFNULL(fdl.withdrawal_user_amount, 0) withdrawal_user_amount,
								 IFNULL(o.bet_user_amount, 0) bet_user_amount,
                 IFNULL(o.prize_user_amount, 0) prize_user_amount,
                 IFNULL(fdl.coupon_user_amount, 0) coupon_user_amount,
                 IFNULL(fdl.return_user_amount, 0) return_user_amount,
                 IFNULL(fdl.turn_card_user_winnings, 0) turn_card_user_winnings,
                 IFNULL(fdl.promotion_user_winnings, 0) promotion_user_winnings,
								 if(u.first_recharge_time >= "', v_start_time, '" and u.first_recharge_time < "', v_end_time, '",1,0) first_deposit,
                 IFNULL(b.back_user_amount, 0) back_user_amount,
								 u.created register_time
            FROM (SELECT "', v_begin_date, '" count_date, user_id
                    FROM orders
                   WHERE order_time >= "', v_start_time, '"
									 AND order_time < "', v_end_time, '"
                  UNION
                  SELECT "', v_begin_date, '" count_date, user_id
                    FROM funds_deal_log
                   WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309)
                     AND created >= "', v_start_time, '"
                     AND created < "', v_end_time, '"
                  UNION
                  SELECT "', v_begin_date, '" count_date, user_id
                    FROM bkge
                   WHERE created >= "', v_start_time, '"
                     AND created < "', v_end_time, '") t
           INNER JOIN user u
              ON u.id = t.user_id
            LEFT JOIN user_agent ua
              ON ua.user_id = t.user_id
            LEFT JOIN profile p
              ON p.user_id = t.user_id
            LEFT JOIN (SELECT user_id,
                              IFNULL(SUM(CASE WHEN deal_type in (101, 102, 106) THEN deal_money ELSE 0 END) / 100, 0) deposit_user_amount,
                              IFNULL(SUM(CASE WHEN deal_type in (201, 204) THEN deal_money ELSE 0 END) / 100, 0) withdrawal_user_amount,
                              IFNULL(SUM(CASE WHEN deal_type in (105, 114) THEN deal_money ELSE 0 END) / 100, 0) coupon_user_amount,
                              IFNULL(SUM(CASE WHEN deal_type in (107, 109,113) THEN deal_money ELSE 0 END) / 100, 0) return_user_amount,
		                          IFNULL(SUM(CASE WHEN deal_type in (309) THEN deal_money ELSE 0 END) / 100, 0) turn_card_user_winnings,
		                          IFNULL(SUM(CASE WHEN deal_type in (308) THEN deal_money ELSE 0 END) / 100, 0) promotion_user_winnings
                        FROM funds_deal_log
                       WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309)
                         AND created >= "', v_start_time, '"
                         AND created < "', v_end_time, '"
                       GROUP BY user_id) fdl
              ON fdl.user_id = t.user_id
            LEFT JOIN (SELECT user_id,  IFNULL(SUM(bkge) / 100, 0) back_user_amount
                        FROM bkge
                       WHERE created >= "', v_start_time, '"
                         AND created < "', v_end_time, '"
                       GROUP BY user_id) b
              ON b.user_id = t.user_id
            LEFT JOIN (SELECT user_id,
                              IFNULL(SUM(bet) / 100, 0) bet_user_amount,
                              IFNULL(SUM(send_money) / 100, 0) prize_user_amount
                        FROM orders
                       WHERE  order_time >= "', v_start_time, '"
													AND order_time < "', v_end_time, '"
                       GROUP BY user_id) o
              ON o.user_id = t.user_id
		   WHERE u.tags != 4) a
      ON DUPLICATE KEY UPDATE count_date = a.count_date,
   user_id = a.user_id,
   user_name = a.user_name,
   real_name = a.real_name,
   superior_id = a.superior_id,
   deposit_user_amount = a.deposit_user_amount,
   withdrawal_user_amount = a.withdrawal_user_amount,
   bet_user_amount = a.bet_user_amount,
   prize_user_amount = a.prize_user_amount,
   coupon_user_amount = a.coupon_user_amount,
   return_user_amount = a.return_user_amount,
   turn_card_user_winnings = a.turn_card_user_winnings,
   promotion_user_winnings = a.promotion_user_winnings,
	 first_deposit = a.first_deposit,
   back_user_amount = a.back_user_amount,
   register_time = a.register_time;');
        SELECT @sqlstr_01;
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;

		SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
	END WHILE;

	UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_user';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_userlottery_earnlose
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_userlottery_earnlose`;
delimiter ;;
CREATE PROCEDURE `p_rpt_userlottery_earnlose`(IN `v_begin_date` varchar(15))
BEGIN
	DECLARE now_date date;
	DECLARE v_yesterday date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;

IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name='rpt_userlottery_earnlose') THEN
   INSERT INTO rpt_exec_his(tab_name,exec_date)
   SELECT 'rpt_userlottery_earnlose',DATE(MIN(created)) FROM send_prize ;
END IF;

IF v_begin_date='' THEN
	SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name='rpt_userlottery_earnlose';
END IF;


DELETE FROM rpt_userlottery_earnlose WHERE count_date>=v_begin_date;

set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));
WHILE v_begin_date<=now_date
	DO
		SET v_yesterday = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
		SET v_start_time = CONCAT(v_yesterday, ' 20:00:00');
		SET v_end_time = CONCAT(v_begin_date, ' 20:00:00');

		INSERT INTO rpt_userlottery_earnlose
		SELECT v_begin_date,b.lottery_id,a.user_id,count(*) bet_num,SUM(a.pay_money) bet_money,SUM(a.money) send_money,SUM(a.lose_earn) lose_earn,NOW()
		FROM send_prize a
		JOIN lottery_order b ON a.order_number=b.order_number AND a.user_id=b.user_id
		JOIN `user` c ON a.user_id=c.id
		WHERE c.tags not in(4,7) AND a.created>=v_start_time  AND a.created<v_end_time
		GROUP BY a.user_id,b.lottery_id;

		SET v_begin_date=DATE_ADD(v_begin_date,INTERVAL 1 DAY);

END WHILE;

		UPDATE rpt_exec_his SET exec_date=DATE_ADD(v_begin_date,INTERVAL -2 DAY) WHERE tab_name='rpt_userlottery_earnlose';

END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p_rpt_userreport
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_userreport`;
delimiter ;;
CREATE PROCEDURE `p_rpt_userreport`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE now_date date;
	DECLARE v_end_date date;

	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_userreport') THEN
		INSERT INTO rpt_exec_his(tab_name,exec_date)
			SELECT 'rpt_userreport', DATE(MIN(created)) FROM funds_deal_log ;
	END IF;

	IF v_begin_date='' THEN
		SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_userreport';
	END IF;

	SET v_end_date = DATE_ADD(v_begin_date,INTERVAL 1 DAY);

	DELETE FROM rpt_userreport WHERE count_date >= v_begin_date;
  set now_date = CONVERT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), CHAR(12));
	WHILE v_begin_date <= now_date DO

		SET v_end_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
		SET @sqlstr_01 = CONCAT(
'INSERT INTO rpt_userreport
  SELECT "', v_begin_date, '",
         t0.id,
         t0.agent_id,
         IFNULL(t9.agent_cnt_inc, 0) agent_cnt_inc,
         IFNULL(t10.inferisors_all, 0) team_cnt,
         (SELECT COUNT(*)
            FROM user_agent
           WHERE created >= UNIX_TIMESTAMP("', v_begin_date, ' 00:00:00")
             AND created < UNIX_TIMESTAMP("', v_end_date, ' 00:00:00")
             AND CONCAT(",", uid_agents) LIKE CONCAT("%,", t0.id, ",%")) team_num_inc,
         t0.`name`,
         deposit_money,
         get_money,
         lottery_valid_bet_money,
         lottery_win_prize,
         lottery_back_moeny,
         lottery_earnlose,
         live_valid_bet_money,
         live_win_prize,
         live_back_moeny,
         live_earnlose,
         game_valid_bet_money,
         game_win_prize,
         game_back_moeny,
         game_earnlose,
         sport_valid_bet_money,
         sport_win_prize,
         sport_back_moeny,
         sport_earnlose,
         return_money,
         coupon_money,
		 turn_card_winnings,
		 promotion_winnings,
         NOW()
    FROM `user` t0
   INNER JOIN (SELECT uid_agent user_id
                 FROM user_agent
                WHERE created >= UNIX_TIMESTAMP("', v_begin_date, ' 00:00:00")
                  AND created < UNIX_TIMESTAMP("', v_end_date, ' 00:00:00")
               UNION
               SELECT user_id
                 FROM funds_deal_log
                WHERE deal_type IN (101, 102, 106, 201, 204, 107, 113, 105, 114, 104, 202, 308, 309)
                  AND created >= "', v_begin_date, '"
                  AND created < "', v_end_date, '"
               UNION
               SELECT user_id
                 FROM order_3th
                WHERE created >= "', v_begin_date, '"
                  AND created < "', v_end_date, '"
               UNION
               SELECT user_id
                 FROM user_rake_log
                WHERE created >= UNIX_TIMESTAMP("', v_begin_date, ' 00:00:00")
                  AND created < UNIX_TIMESTAMP("', v_end_date, ' 00:00:00")) t1
      ON t0.id = t1.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) deposit_money
                 FROM funds_deal_log c
                WHERE c.deal_type IN (101, 102, 106)
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t2
      ON t0.id = t2.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) get_money
                 FROM funds_deal_log c
                WHERE c.deal_type IN (201, 204)
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t3
      ON t0.id = t3.user_id
    LEFT JOIN (SELECT c.user_id,
                      SUM(c.pay_money) lottery_valid_bet_money,
                      SUM(IFNULL(c.money, 0)) lottery_win_prize,
                      SUM(IFNULL(c.lose_earn, 0)) lottery_earnlose
                 FROM send_prize c
                WHERE c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t4
      ON t0.id = t4.user_id
    LEFT JOIN (SELECT user_id,
                      SUM( CASE WHEN type = "1" THEN back_money ELSE 0 END) game_back_moeny,
                      SUM( CASE WHEN type = "2" THEN back_money ELSE 0 END) live_back_moeny,
                      SUM( CASE WHEN type = "3" THEN back_money ELSE 0 END) sport_back_moeny,
                      SUM( CASE WHEN type = "4" THEN back_money ELSE 0 END) lottery_back_moeny
                 FROM user_rake_log c
                WHERE c.created >= UNIX_TIMESTAMP("', v_begin_date, ' 00:00:00")
                  AND c.created < UNIX_TIMESTAMP("', v_end_date, ' 00:00:00")
                GROUP BY c.user_id) t5
      ON t0.id = t5.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) return_money
                 FROM funds_deal_log c
                WHERE c.deal_type IN (107, 113)
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t6
      ON t0.id = t6.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) coupon_money
                 FROM funds_deal_log c
                WHERE c.deal_type IN (105, 114)
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t7
      ON t0.id = t7.user_id
    LEFT JOIN (SELECT c.user_id,
                      SUM( CASE WHEN order_type = 1 THEN valid_money ELSE 0 END) game_valid_bet_money,
                      SUM( CASE WHEN order_type = 1 THEN prize ELSE 0 END) game_win_prize,
                      SUM( CASE WHEN order_type = 1 THEN win_loss ELSE 0 END) game_earnlose,
                      SUM( CASE WHEN order_type = 2 THEN valid_money ELSE 0 END) live_valid_bet_money,
                      SUM( CASE WHEN order_type = 2 THEN prize ELSE 0 END) live_win_prize,
                      SUM( CASE WHEN order_type = 2 THEN win_loss ELSE 0 END) live_earnlose,
                      SUM( CASE WHEN order_type = 3 THEN valid_money ELSE 0 END) sport_valid_bet_money,
                      SUM( CASE WHEN order_type = 3 THEN prize ELSE 0 END) sport_win_prize,
					  SUM(CASE WHEN order_type = 3 THEN win_loss ELSE 0 END) sport_earnlose
                 FROM order_3th c
                WHERE c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t8
      ON t0.id = t8.user_id
    LEFT JOIN (SELECT uid_agent, COUNT(*) agent_cnt_inc
                 FROM user_agent
                WHERE created >= UNIX_TIMESTAMP("', v_begin_date, ' 00:00:00")
                  AND created < UNIX_TIMESTAMP("', v_end_date, ' 00:00:00")
                GROUP BY uid_agent) t9
      ON t0.id = t9.uid_agent
    LEFT JOIN user_agent t10
      ON t0.id = t10.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) turn_card_winnings
                 FROM funds_deal_log c
                WHERE c.deal_type = 309
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t11
      ON t0.id = t11.user_id
    LEFT JOIN (SELECT c.user_id, SUM(IFNULL(deal_money, 0)) promotion_winnings
                 FROM funds_deal_log c
                WHERE c.deal_type = 308
                  AND c.created >= "', v_begin_date, '"
                  AND c.created < "', v_end_date, '"
                GROUP BY c.user_id) t12
      ON t0.id = t12.user_id
   WHERE t0.tags NOT IN (4, 7);');
	PREPARE sqlstr_01 FROM @sqlstr_01;
	EXECUTE sqlstr_01;
	DEALLOCATE PREPARE sqlstr_01;

		SET  v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
	END WHILE;

	UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -2 DAY) WHERE tab_name = 'rpt_userreport';
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
