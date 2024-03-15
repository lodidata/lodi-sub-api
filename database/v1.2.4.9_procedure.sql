

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Procedure structure for p_rpt_channel
-- ----------------------------
DROP PROCEDURE IF EXISTS `p_rpt_channel`;
delimiter ;;
CREATE PROCEDURE `p_rpt_channel`(IN `v_begin_date` varchar(15))
BEGIN
  DECLARE v_end_date DATE;
  DECLARE v_start_time DATETIME;
  DECLARE v_end_time DATETIME;
	
	IF NOT EXISTS (SELECT 1 FROM rpt_exec_his WHERE tab_name = 'rpt_channel') THEN 
	  INSERT INTO rpt_exec_his (tab_name, exec_date)
	  SELECT 'rpt_channel', DATE(MAX(count_date)) FROM rpt_user;
	END IF;
	IF v_begin_date = '' THEN 
	  SELECT exec_date INTO v_begin_date FROM rpt_exec_his WHERE tab_name = 'rpt_channel';
	END IF;

		SET v_end_date = DATE_ADD( v_begin_date, INTERVAL 1 DAY );
		SET v_start_time = CONCAT( v_begin_date, ' 00:00:00' );
		SET v_end_time = CONCAT( v_end_date, ' 00:00:00' );
		SET @sqlstr_01 = CONCAT(
		'INSERT INTO rpt_channel (count_date,channel_id,channel_name,award_money,click
		,cz_amount,cz_person,qk_person,qk_amount,tz_amount,pc_amount,hd_amount,hs_amount,js_amount,
		zk_amount,fyz_amount,first_recharge_user,first_recharge,first_withdraw,first_bet,first_prize)
			SELECT * FROM (
			SELECT
      "',v_begin_date,'" as count_date,
			cm.number as channel_id,
			cm.name as channel_name,
			IFNULL(rpts.award_money,0) as award_money,
			IFNULL(ck2.click, 0) as click,
			IFNULL(rpts.cz_amount, 0) as cz_amount,
			IFNULL(rpts.cz_person, 0) as cz_person,
			IFNULL(rpts.qk_person, 0) as qk_person,
			IFNULL(rpts.qk_amount, 0) as qk_amount,
			IFNULL(rpts.tz_amount, 0) as tz_amount,
			IFNULL(rpts.pc_amount, 0) as pc_amount,
			IFNULL(rpts.hd_amount, 0) as hd_amount,
			IFNULL(rpts.hs_amount, 0) as hs_amount,
			IFNULL(rpts.js_amount, 0) as js_amount,
			IFNULL(rpts.zk_amount, 0) as zk_amount,
			IFNULL(rpts.fyz_amount, 0) as fyz_amount,
			IFNULL(rpts.first_recharge_user, 0) as first_recharge_user,
			IFNULL(rpts.first_recharge, 0) as first_recharge,
			IFNULL(rpts.first_withdraw, 0) as first_withdraw,
			IFNULL(rpts.first_bet, 0) as first_bet,
			IFNULL(rpts.first_prize, 0) as first_prize
			FROM 
			`channel_management` cm
LEFT JOIN ( SELECT IFNULL(channel_id,"default") as channel_id, count( log_ip ) as click from user_channel_logs WHERE created >= "',v_start_time,'" AND created < "',v_end_time,'" GROUP BY channel_id ) ck2 ON cm.number = ck2.channel_id
	    LEFT JOIN ( SELECT 
			rpt_user.count_date,
			user.channel_id,
			IFNULL( yf.award_money, 0 ) as award_money,
			sum( rpt_user.deposit_user_amount ) as cz_amount,
			sum(IF (rpt_user.deposit_user_amount> 0,1,0)) as cz_person,
			sum(IF (rpt_user.withdrawal_user_amount> 0,1,0)) as qk_person,
			sum( rpt_user.withdrawal_user_amount ) as qk_amount,
			sum( rpt_user.bet_user_amount ) as tz_amount,
			sum( rpt_user.prize_user_amount ) as pc_amount,
			sum( rpt_user.coupon_user_amount ) as hd_amount,
			sum( rpt_user.return_user_amount ) as hs_amount,
			sum( rpt_user.promotion_user_winnings ) as js_amount,
			sum( rpt_user.turn_card_user_winnings ) as zk_amount,
			sum( rpt_user.back_user_amount ) as fyz_amount,
			sum(IF (rpt_user.first_deposit> 0,1,0)) as first_recharge_user,
			sum(IF (rpt_user.first_deposit> 0,rpt_user.deposit_user_amount,0)) as first_recharge, 
			sum(IF (rpt_user.first_deposit> 0,rpt_user.withdrawal_user_amount,0)) as first_withdraw,
			sum(IF (rpt_user.first_deposit> 0,rpt_user.bet_user_amount,0)) as first_bet,
			sum(IF (rpt_user.first_deposit> 0,rpt_user.prize_user_amount,0)) as first_prize
			from
			`rpt_user`
			left join `user` on `user`.`id` = `rpt_user`.`user_id`
      left join ( SELECT lt.channel_id,lt.id,SUM(user_monthly_award.award_money)/100 as award_money from (select DISTINCT user.channel_id,user.id from `rpt_user` left join `user` on `user`.`id`=`rpt_user`.`user_id` where rpt_user.count_date="',v_begin_date,'" and user.channel_id is not null) as lt
LEFT JOIN `user_monthly_award` on `user_monthly_award`.`user_id`=lt.id and `user_monthly_award`.`award_date`="',v_begin_date,'" GROUP BY lt.channel_id
			) yf ON yf.channel_id = user.channel_id
      where
			rpt_user.count_date = "',v_begin_date,'" 
			and user.channel_id is not null 
			group by `user`.`channel_id` ) rpts ON cm.number = rpts.channel_id
			) a
						
   ON DUPLICATE KEY UPDATE 
	 count_date = a.count_date,
   channel_id = a.channel_id,
   channel_name = a.channel_name,
   award_money = a.award_money,
   click = a.click,
   cz_amount = a.cz_amount,
	 cz_person = a.cz_person,
   qk_person = a.qk_person,
   qk_amount = a.qk_amount,
   tz_amount = a.tz_amount,
   pc_amount = a.pc_amount,
   hd_amount = a.hd_amount,
   hs_amount = a.hs_amount,
   js_amount = a.js_amount,
   zk_amount = a.zk_amount,
   fyz_amount = a.fyz_amount,
   first_recharge_user = a.first_recharge_user,
   first_recharge   = a.first_recharge,
   first_withdraw = a.first_withdraw,
   first_bet = a.first_bet,
	 first_prize = a.first_prize;');

    SELECT @sqlstr_01;
		PREPARE sqlstr_01 FROM @sqlstr_01;
		EXECUTE sqlstr_01;
		DEALLOCATE PREPARE sqlstr_01;

	UPDATE rpt_exec_his SET exec_date = DATE_ADD(v_begin_date, INTERVAL 1 DAY) WHERE tab_name = 'rpt_channel';
END

;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;