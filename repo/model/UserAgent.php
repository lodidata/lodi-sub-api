<?php
namespace Model;
use DB;
class UserAgent extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'user_agent';

    public $timestamps = false;

    protected $fillable = [
                            'id',
                            'user_id',
                            'uid_agent',
                            'uid_agents',
                            'uid_agent_name',
                            'code',
                            'inferisors_num',
                            'level',
                            'type',
                            'channel',
                            'earn_money',
                            'bkge_json',
                            'junior_bkge',
                            'direct_reg',
                            ];
                            
    public static function boot() {
        parent::boot();

        static::creating(function ($obj) {
            $obj->created = time();
            $obj->updated = time();
        });

        // static::updating(function ($obj) {
        //     $obj->updated = time();
        // });
    }

    public static function getAgentInfo($uidAgent) {
        $sql = "SELECT t.user_id,s.`name`,s.created,(CASE s.state WHEN '0' THEN '禁用' WHEN '1' THEN '启用' END ) as state,(CASE s.channel WHEN 'register' THEN '网站注册' WHEN 'partner' THEN '第三方' WHEN 'reserved' THEN '保留' ELSE '其他' END) as channel,(select count(*) FROM user_agent WHERE uid_agent=t.user_id) team_cnt, p.`name` AS truename,l.title AS tags,p.email,
            p.qq,p.weixin,bkge_lottery,bkge_sport,bkge_live,bkge_game,
             CONCAT(s.telphone_code,s.mobile) mobile,  SUM(back_money) back_money,
            
             SUM(lottery_back) sum_lottery_back,SUM(game_back) sum_game_back,SUM(live_back) sum_live_back,SUM(sport_back) sum_sport_back,
             SUM(lottery_bet) sum_lottery_bet,SUM(game_bet) sum_game_bet,SUM(live_bet) sum_live_bet,SUM(sport_bet) sum_sport_bet,
             ifnull(SUM(lottery_bet),0)+IFNULL(game_bet,0)+IFNULL(live_bet,0)+IFNULL(sport_bet,0) sum_bet
            FROM 
            (
                    SELECT  t1.user_id,t1.bkge_lottery,t1.bkge_sport,t1.bkge_live,t1.bkge_game,
                                     (SELECT SUM(back_money) FROM user_rake_log a WHERE a.user_id=uid_agent and a.order_user_id=t2.lower_id) back_money,
                                    
                                    (SELECT SUM(pay_money)  FROM lottery_order a WHERE user_id = t2.lower_id  and find_in_set('open',state)) as lottery_bet,
                                    (SELECT SUM(money)  FROM order_3th a WHERE user_id = t2.lower_id  and a.order_type = 1) as game_bet,
                                    (SELECT SUM(money)  FROM order_3th a WHERE user_id = t2.lower_id  and a.order_type = 2) as live_bet,
                                    (SELECT SUM(money)  FROM order_3th a WHERE user_id = t2.lower_id  and a.order_type = 3 and a.status = 1) as sport_bet,
                                    (SELECT SUM(back_money) FROM user_rake_log a WHERE a.type = 4 and a.user_id=uid_agent and a.order_user_id=t2.lower_id) lottery_back,
                                    (SELECT SUM(back_money) FROM user_rake_log a WHERE a.type = 1 and a.user_id=uid_agent and a.order_user_id=t2.lower_id) game_back,
                                    (SELECT SUM(back_money) FROM user_rake_log a WHERE a.type = 2 and a.user_id=uid_agent and a.order_user_id=t2.lower_id) live_back,
                                    (SELECT SUM(back_money) FROM user_rake_log a WHERE a.type = 3 and a.user_id=uid_agent and a.order_user_id=t2.lower_id) sport_back
                    FROM user_agent t1 
                    JOIN(       
                            select substring_index(substring_index(a.uid_agents,',',b.help_category_id+1),',',-1) user_id,user_id lower_id
                            from user_agent a
                            join mysql.help_category b on b.help_category_id < (length(a.uid_agents) - length(replace(a.uid_agents,',',''))+1)
                            )t2 ON t1.user_id = t2.user_id
                    WHERE t1.user_id = $uidAgent
            ) t
            JOIN `user` s ON t.user_id = s.id AND s.tags not in (4,7)
            LEFT JOIN `profile` p ON p.user_id = s.id
            LEFT JOIN label l ON s.tags = l.id
            GROUP BY t.user_id ";
        return DB::select($sql);
    }

    /**
     * 获取邀请码
     * @param $userId
     * @return mixed
     */
    public static function getCode($userId){
        return self::where('user_id', $userId)->value('code');
    }

    public static function getRecords($condition, $page = 1, $pageSize = 30) {
        $where = [];
        $wheresql = '';
        $timesql = '';
        isset($condition['name']) && $where[] = " s.`name` = '{$condition['name']}'";
        isset($condition['state']) && $where[] = " s.state " . (preg_match('~^\s*[<>\=!].*~',
            $condition['state']) ? '' : '=') . " {$condition['state']} ";
        isset($condition['uid_agent']) && $wheresql = " WHERE t1.uid_agent = '{$condition['uid_agent']}'";
        if(isset($condition['start_time'])){
//            $where[] = " s.created >= '{$condition['start_time']}'";
            $timesql = " AND a.created >= '{$condition['start_time']}'";
        }
        if(isset($condition['end_time'])){
//            $where[] = " s.created <= '{$condition['end_time']}'";
            $timesql .= " AND a.created >= '{$condition['end_time']}'" ;
        }

        //真实姓名
        $where = count($where) ? 'WHERE ' . implode(' AND', $where) : '';

        $sql = "SELECT SQL_CALC_FOUND_ROWS s.id,t.user_id,s.`name`,team_cnt,s.channel,
                CASE s.channel WHEN 'register' THEN '网站注册' WHEN 'partner' THEN '第三方' WHEN 'reserved' THEN '保留' ELSE s.channel END AS channel_str,
                s.created,s.state,
             CONCAT(s.telphone_code,s.mobile) mobile,IFNULL(SUM(back_money),0) back_money,IFNULL(SUM(deposit_money),0) deposit_money,IFNULL(SUM(pay_money),0) pay_money
            FROM 
            (
                    SELECT  t1.user_id,
                                    (SELECT SUM(back_money) FROM user_rake_log a WHERE a.user_id=uid_agent and a.order_user_id=t2.lower_id AND state=1 $timesql) back_money,
                                    (SELECT sum(money) FROM funds_deposit a where a.status = 'paid' AND a.user_id=t2.lower_id $timesql) deposit_money,
                                    (SELECT sum(pay_money) FROM send_prize a where  a.user_id=t2.lower_id $timesql) pay_money,
                                    inferisors_num as team_cnt
                    FROM user_agent t1 
                    JOIN(       
                            select substring_index(substring_index(a.uid_agents,',',b.help_category_id+1),',',-1) user_id,user_id lower_id
                            from user_agent a
                            join mysql.help_category b on b.help_category_id < (length(a.uid_agents) - length(replace(a.uid_agents,',',''))+1)
                            )t2 ON t1.user_id = t2.user_id
                    {$wheresql}  
            ) t
            JOIN `user` s ON t.user_id = s.id {$where}
            GROUP BY t.user_id order by s.id DESC LIMIT " . ($pageSize) . " OFFSET " . ($page - 1) * $pageSize;
        
        return [DB::select($sql), current(DB::selectOne("select FOUND_ROWS()"))];
    }
}
