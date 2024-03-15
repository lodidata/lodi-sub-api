SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Procedure structure for p_rpt_user
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_user`;
delimiter ;;
CREATE PROCEDURE `p_rpt_user`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE now_date DATE;
  DECLARE v_end_date DATE;
  DECLARE v_start_time DATETIME;
  DECLARE v_end_time DATETIME;
  IF NOT EXISTS (
    SELECT 1
    FROM rpt_exec_his
    WHERE tab_name = 'rpt_user'
  ) THEN
    INSERT INTO rpt_exec_his (tab_name, exec_date)
SELECT 'rpt_user', DATE(MIN(created))
FROM funds_deal_log;
END IF;
  IF v_begin_date = '' THEN
SELECT exec_date
INTO v_begin_date
FROM rpt_exec_his
WHERE tab_name = 'rpt_user';
END IF;
  SET now_date = CURDATE();
  WHILE v_begin_date <= now_date DO
  SET v_end_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
  SET v_start_time = CONCAT(v_begin_date, ' 00:00:00');
  SET v_end_time = CONCAT(v_end_date, ' 00:00:00');
  SET @sqlstr_01 = CONCAT('INSERT INTO rpt_user
  (count_date,
   user_id,
   user_name,
   real_name,
   superior_id,
   deposit_user_amount,
   deposit_user_cnt,
   withdrawal_user_amount,
   withdrawal_user_cnt,
   rebate_withdraw_amount,
   rebate_withdraw_cnt,
   manual_deduction_amount,
   bet_user_amount,
   dml,
   prize_user_amount,
   coupon_user_amount,
   return_user_amount,
   turn_card_user_winnings,
   promotion_user_winnings,
         first_deposit,
   back_user_amount,
   register_time) select * from (SELECT * FROM (SELECT "', v_begin_date, '" count_date  ,u.id user_id
    , u.name AS user_name
    , p.name AS real_name
    , ua.uid_agent AS superior_id
    , IFNULL(fdl.deposit_user_amount, 0) AS deposit_user_amount
    ,IFNULL(fdl.deposit_user_cnt, 0) AS deposit_user_cnt
    ,IFNULL(fdl.withdrawal_user_amount, 0) AS withdrawal_user_amount
    ,IFNULL(fdl.withdrawal_user_cnt, 0) AS withdrawal_user_cnt
    ,IFNULL(fdl.rebate_withdraw_amount, 0) AS rebate_withdraw_amount
    ,IFNULL(fdl.rebate_withdraw_cnt, 0) AS rebate_withdraw_cnt
    , IFNULL(fdl.manual_deduction_amount, 0) AS manual_deduction_amount
    , IFNULL(o.bet_user_amount, 0) AS bet_user_amount
    , IFNULL(o.dml, 0) AS dml
    , IFNULL(o.prize_user_amount, 0) AS prize_user_amount
    , IFNULL(fdl.coupon_user_amount, 0) AS coupon_user_amount
    , IFNULL(fdl.return_user_amount, 0) AS return_user_amount
    , IFNULL(fdl.turn_card_user_winnings, 0) AS turn_card_user_winnings
    , IFNULL(fdl.promotion_user_winnings, 0) AS promotion_user_winnings
    , if(u.first_recharge_time >= "', v_start_time, '" AND u.first_recharge_time < "', v_end_time, '",1, 0) AS first_deposit
    , IFNULL(b.back_user_amount, 0) AS back_user_amount, u.created AS register_time
  FROM `user` u
    LEFT JOIN (
      SELECT user_id
        , IFNULL(SUM(CASE
          WHEN deal_type IN (101, 102, 106) THEN deal_money
          ELSE 0
        END) / 100, 0) AS deposit_user_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (101, 102) THEN 1
          WHEN deal_type=106 and deal_money>0 THEN 1
          ELSE 0
        END), 0) AS deposit_user_cnt
        ,IFNULL(SUM(CASE
          WHEN deal_type IN (201, 204) THEN deal_money
          ELSE 0
        END) / 100, 0) AS withdrawal_user_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (201, 204) THEN 1
          ELSE 0
        END), 0) AS withdrawal_user_cnt
        ,IFNULL(SUM(CASE
          WHEN deal_type IN (218, 311) THEN deal_money
          ELSE 0
        END) / 100, 0) AS rebate_withdraw_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (218, 311) THEN 1
          ELSE 0
        END), 0) AS rebate_withdraw_cnt
        ,IFNULL(SUM(CASE
          WHEN deal_type = 204 THEN deal_money
          ELSE 0
        END) / 100, 0) AS manual_deduction_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (702,703,105, 114, 121, 122, 123) THEN deal_money
          ELSE 0
        END) / 100, 0) AS coupon_user_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (701, 109, 113) THEN deal_money
          ELSE 0
        END) / 100, 0) AS return_user_amount
        , IFNULL(SUM(CASE
          WHEN deal_type IN (309) THEN deal_money
          ELSE 0
        END) / 100, 0) AS turn_card_user_winnings
        , IFNULL(SUM(CASE
          WHEN deal_type IN (308) THEN deal_money
          ELSE 0
        END) / 100, 0) AS promotion_user_winnings
      FROM funds_deal_log
      WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309,701,702,703, 121, 122, 123, 218, 311)
        AND created >=  "', v_start_time, '"
        AND created < "', v_end_time, '"
      GROUP BY user_id
    ) fdl
    ON u.id = fdl.user_id
    LEFT JOIN (
      SELECT user_id
        , IFNULL(SUM(bkge) / 100, 0) AS back_user_amount
      FROM bkge
      WHERE created >= "', v_start_time, '"
        AND created < "', v_end_time, '"
      GROUP BY user_id
    ) b
    ON b.user_id = u.id
    LEFT JOIN (
      SELECT user_id
        , IFNULL(SUM(bet) / 100, 0) AS bet_user_amount
        , IFNULL(SUM(dml) / 100, 0) AS dml
        , IFNULL(SUM(send_money) / 100, 0) AS prize_user_amount
      FROM order_game_user_middle
      WHERE `date` = "', v_begin_date, '"
      GROUP BY user_id
    ) o
    ON o.user_id = u.id
    LEFT JOIN user_agent ua ON ua.user_id = u.id
    LEFT JOIN profile p ON p.user_id = u.id
  WHERE u.tags != 4
) x WHERE abs(deposit_user_amount) + abs(withdrawal_user_amount) + abs(rebate_withdraw_amount) + abs(coupon_user_amount) + abs(bet_user_amount) + abs(prize_user_amount) + abs(coupon_user_amount) + abs(return_user_amount) + abs(turn_card_user_winnings) + abs(promotion_user_winnings) + abs(back_user_amount) > 0) y
ON DUPLICATE KEY UPDATE count_date = y.count_date,
     user_id = y.user_id,
     user_name = y.user_name,
     real_name = y.real_name,
     superior_id = y.superior_id,
     deposit_user_amount = y.deposit_user_amount,
     deposit_user_cnt    = y.deposit_user_cnt,
     withdrawal_user_amount = y.withdrawal_user_amount,
     withdrawal_user_cnt = y.withdrawal_user_cnt,
     rebate_withdraw_amount = y.rebate_withdraw_amount,
     rebate_withdraw_cnt = y.rebate_withdraw_cnt,
     manual_deduction_amount = y.manual_deduction_amount,
     bet_user_amount = y.bet_user_amount,
     dml = y.dml,
     prize_user_amount = y.prize_user_amount,
     coupon_user_amount = y.coupon_user_amount,
     return_user_amount = y.return_user_amount,
     turn_card_user_winnings = y.turn_card_user_winnings,
     promotion_user_winnings = y.promotion_user_winnings,
     first_deposit = y.first_deposit,
     back_user_amount = y.back_user_amount,
     register_time = y.register_time;');
SELECT @sqlstr_01;
PREPARE sqlstr_01 FROM @sqlstr_01;
EXECUTE sqlstr_01;
DEALLOCATE PREPARE sqlstr_01;
SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
END WHILE;
UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -1 DAY) WHERE tab_name = 'rpt_user';
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;