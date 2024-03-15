/*
 Navicat Premium Data Transfer

 Source Server         : dev
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : 52.74.208.242:3308
 Source Schema         : lodi2_game

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 16/08/2022 10:55:06
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Procedure structure for p_rpt_deposit_withdrawal_day
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_deposit_withdrawal_day`;
delimiter ;;
CREATE DEFINER=`catchadmin`@`%` PROCEDURE `p_rpt_deposit_withdrawal_day`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE now_date date;
	DECLARE v_end_date date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;
	DECLARE v_count_date date;
	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_deposit_withdrawal_day') THEN
		INSERT INTO rpt_exec_his (tab_name, exec_date)
SELECT 'rpt_deposit_withdrawal_day', DATE(MIN(created)) FROM funds_deal_log;
END IF;
	IF v_begin_date = '' THEN
SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_deposit_withdrawal_day';
END IF;
	set now_date = CURDATE();
	WHILE v_begin_date <= now_date DO
	SET v_end_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
    SET v_count_date = DATE_ADD(v_begin_date, INTERVAL -1 DAY);
	SET v_start_time = CONCAT(v_begin_date, ' 00:00:00');
	SET v_end_time = CONCAT(v_end_date, ' 00:00:00');
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
   confiscate_amount,
   confiscate_cnt,
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
                 IFNULL(fdl.confiscate_cnt, 0) confiscate_cnt,
                 IFNULL(fdl.confiscate_amount, 0) confiscate_amount,
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
                   WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 213, 308, 309, 501)
				     AND created >= "', v_start_time, '"
                     AND created < "', v_end_time, '"
                  UNION
                  SELECT "', v_begin_date, '" count_date
                    FROM bkge url
                   WHERE created >= "', v_start_time, '"
                     AND created < "', v_end_time, '") t
            LEFT JOIN (SELECT "', v_begin_date, '" count_date,
                              SUM(CASE WHEN fdl1.deal_type in (101, 102, 106) THEN fdl1.deal_money ELSE 0 END) / 100 income_amount,
                              SUM(CASE WHEN fdl1.deal_type in (201,218) THEN fdl1.deal_money ELSE 0 END) / 100 withdrawal_amount,
                              SUM(CASE WHEN fdl1.deal_type in (201,218) THEN 1 ELSE 0 END) withdrawal_cnt,
                              SUM(CASE WHEN fdl1.deal_type in (213,219) THEN fdl1.deal_money ELSE 0 END) / 100 confiscate_amount,
                              SUM(CASE WHEN fdl1.deal_type in (213,219) THEN 1 ELSE 0 END) confiscate_cnt,
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
                       WHERE fdl1.deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201,218, 219,204, 213, 308, 309, 501)
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
   confiscate_amount = a.confiscate_amount,
   confiscate_cnt = a.confiscate_cnt,
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
SET @sqlstr_05 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(SELECT "', v_begin_date, '" count_date,ifnull(sum(is_valid_agent),0) new_valid_agent_num FROM `rpt_agent`
						 WHERE count_date = "', v_begin_date, '") r
		ON rdwd.count_date = r.count_date
		SET rdwd.new_valid_agent_num = r.new_valid_agent_num
						 ;');
SELECT @sqlstr_05;
PREPARE sqlstr_05 FROM @sqlstr_05;
EXECUTE sqlstr_05;
DEALLOCATE PREPARE sqlstr_05;
SET @sqlstr_06 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(SELECT "', v_begin_date, '" count_date,count(*) agent_first_deposit_num FROM `rpt_user`
						 WHERE count_date = "', v_begin_date, '"
						 AND deposit_user_amount > 0
						 AND superior_id != 0
						 AND register_time >= "', v_start_time, '"
							AND register_time <  "', v_end_time, '" ) r
					ON rdwd.count_date = r.count_date
					SET rdwd.agent_first_deposit_num = r.agent_first_deposit_num
						 ;');
SELECT @sqlstr_06;
PREPARE sqlstr_06 FROM @sqlstr_06;
EXECUTE sqlstr_06;
DEALLOCATE PREPARE sqlstr_06;
SET @sqlstr_07 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(SELECT "', v_count_date, '" date,count( DISTINCT ( user_id ) ) AS num, IFNULL( sum( settle_amount ), 0 ) AS amount FROM `unlimited_agent_bkge`
						 WHERE date = "', v_count_date, '" and settle_amount > 0 and status =1) r
		ON rdwd.count_date = r.date
		SET rdwd.shares_settle_cnt = r.num,
				rdwd.shares_settle_amount = r.amount
						 ;');
SELECT @sqlstr_07;
PREPARE sqlstr_07 FROM @sqlstr_07;
EXECUTE sqlstr_07;
DEALLOCATE PREPARE sqlstr_07;
SET @sqlstr_08 = CONCAT(
		'UPDATE rpt_deposit_withdrawal_day rdwd
    INNER JOIN
			(SELECT "', v_begin_date, '" count_date, ifnull(sum(withdrawal_user_amount),0) new_register_withdraw_amount FROM `rpt_user`
						 WHERE count_date = "', v_begin_date, '"
						 AND withdrawal_user_amount > 0
						 AND register_time >= "', v_start_time, '"
							AND register_time <  "', v_end_time, '" ) r
					ON rdwd.count_date = r.count_date
					SET rdwd.new_register_withdraw_amount = r.new_register_withdraw_amount
						 ;');
SELECT @sqlstr_08;
PREPARE sqlstr_08 FROM @sqlstr_08;
EXECUTE sqlstr_08;
DEALLOCATE PREPARE sqlstr_08;
SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
END WHILE;
UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -1 DAY) WHERE tab_name = 'rpt_deposit_withdrawal_day';
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
