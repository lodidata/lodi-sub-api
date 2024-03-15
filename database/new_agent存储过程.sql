
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
                         IF(SUM(if(u.created >= "', v_start_time, '" and u.created < "', v_end_time ,'" and ru.deposit_user_amount >= 100 ,1,0)) >= 2,1,0) is_valid_agent,
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


DROP PROCEDURE IF EXISTS `p_rpt_user`;
delimiter ;;
CREATE PROCEDURE `p_rpt_user`(IN `v_begin_date` varchar(15))
BEGIN
	DECLARE now_date date;
	DECLARE v_end_date date;
	DECLARE v_start_time datetime;
	DECLARE v_end_time datetime;
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
   withdrawal_user_amount,
	 manual_deduction_amount,
   bet_user_amount,
   prize_user_amount,
   coupon_user_amount,
   return_user_amount,
   turn_card_user_winnings,
   promotion_user_winnings,
         first_deposit,
   back_user_amount,
   register_time) select * from (SELECT * FROM (SELECT "', v_begin_date, '" count_date	,u.id user_id
		, u.name AS user_name
		, p.name AS real_name
		, ua.uid_agent AS superior_id
		, IFNULL(fdl.deposit_user_amount, 0) AS deposit_user_amount
		, IFNULL(fdl.withdrawal_user_amount, 0) AS withdrawal_user_amount
		, IFNULL(fdl.manual_deduction_amount, 0) AS manual_deduction_amount
		, IFNULL(o.bet_user_amount, 0) AS bet_user_amount
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
					WHEN deal_type IN (201, 204) THEN deal_money
					ELSE 0
				END) / 100, 0) AS withdrawal_user_amount
				, IFNULL(SUM(CASE
					WHEN deal_type = 204 THEN deal_money
					ELSE 0
				END) / 100, 0) AS manual_deduction_amount
				, IFNULL(SUM(CASE
					WHEN deal_type IN (105, 114) THEN deal_money
					ELSE 0
				END) / 100, 0) AS coupon_user_amount
				, IFNULL(SUM(CASE
					WHEN deal_type IN (107, 109, 113) THEN deal_money
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
			WHERE deal_type IN (101, 102, 105, 106, 107, 109, 113, 114, 201, 204, 308, 309)
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
				, IFNULL(SUM(send_money) / 100, 0) AS prize_user_amount
			FROM orders FORCE INDEX (idx_order_time)
			WHERE order_time >= "', v_start_time, '"
				AND order_time < "', v_end_time, '"
			GROUP BY user_id
		) o
		ON o.user_id = u.id
		LEFT JOIN user_agent ua ON ua.user_id = u.id
		LEFT JOIN profile p ON p.user_id = u.id
	WHERE u.tags != 4
) x WHERE deposit_user_amount + withdrawal_user_amount + coupon_user_amount + bet_user_amount + prize_user_amount + coupon_user_amount + return_user_amount + turn_card_user_winnings + promotion_user_winnings + back_user_amount > 0) y
ON DUPLICATE KEY UPDATE count_date = y.count_date,
	   user_id = y.user_id,
	   user_name = y.user_name,
	   real_name = y.real_name,
	   superior_id = y.superior_id,
	   deposit_user_amount = y.deposit_user_amount,
	   withdrawal_user_amount = y.withdrawal_user_amount,
		 manual_deduction_amount = y.manual_deduction_amount,
	   bet_user_amount = y.bet_user_amount,
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


DROP PROCEDURE IF EXISTS `p_rpt_deposit_withdrawal_day`;
delimiter ;;
CREATE PROCEDURE `p_rpt_deposit_withdrawal_day`(IN `v_begin_date` varchar(15))
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

SET v_begin_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY);
END WHILE;

UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL -1 DAY) WHERE tab_name = 'rpt_deposit_withdrawal_day';
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
