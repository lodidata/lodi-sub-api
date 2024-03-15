/*
 Navicat Premium Data Transfer

 Source Server         : dev
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : 52.74.208.242:3308
 Source Schema         : lodi_game

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 24/11/2022 15:47:09
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Procedure structure for p_rpt_agent
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_agent`;
delimiter ;;
CREATE PROCEDURE `p_rpt_agent`(IN `v_begin_date` VARCHAR(15))
BEGIN
  DECLARE now_date DATE;
  DECLARE v_end_date DATE;
  DECLARE v_start_time DATETIME;
  DECLARE v_end_time DATETIME;
  IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_agent') THEN
    INSERT INTO rpt_exec_his (tab_name, exec_date)
SELECT 'rpt_agent', DATE(MIN(count_date)) FROM rpt_user;
END IF;
  IF v_begin_date = '' THEN
SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_agent';
END IF;
  SET now_date = CURDATE();

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
	 manual_deduction_amount,
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
   is_valid_agent,
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
												 IFNULL(SUM(IFNULL(ru.manual_deduction_amount, 0)), 0) manual_deduction_amount,
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
                         IF(SUM(if(ru.register_time >= "', v_start_time, '" and ru.register_time < "', v_end_time ,'" and ru.deposit_user_amount >= 100 ,1,0)) >= 2,1,0) is_valid_agent,
                         u.created register_time
                    FROM user_agent ua
                   INNER JOIN `user` u
                      ON ua.user_id = u.id
                   INNER JOIN (select pid, cid from child_agent
                               union
                               select id pid, id cid from user) ca
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
                      SUM(CASE WHEN ca.pid = ca.cid THEN 0 ELSE f.balance/100 END) balance_amount
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
	 manual_deduction_amount = a.manual_deduction_amount,
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
   is_valid_agent   = a.is_valid_agent,
   register_time = a.register_time,
   balance_amount = a.balance_amount;');
SELECT @sqlstr_01;
PREPARE sqlstr_01 FROM @sqlstr_01;
EXECUTE sqlstr_01;
DEALLOCATE PREPARE sqlstr_01;
SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
END WHILE;
UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -1 DAY) WHERE tab_name = 'rpt_agent';
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;