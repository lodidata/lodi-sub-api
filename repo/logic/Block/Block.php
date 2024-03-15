<?php
namespace Logic\Block;
use Model\SendPrize;
use Model\User;
use DB;
/**
 * 区块模块
 */
class Block extends \Logic\Logic {

    /**
     * 创建首页中奖人数缓存
     * @return [type] [description]
     */
    public function createHomePagePrizeData() {


        $sql = "SELECT t1.id,t1.user_id,CONCAT(LEFT(t1.user_name, 3), '***',RIGHT(t1.user_name, 2)) AS user_name,round(t1.money)*100  AS money,l.`name` 
                FROM (SELECT user_id,max(id) maxid 
                            FROM (SELECT user_id,id 
                                        FROM send_prize a 
                                        where  money > 0
                                        ORDER BY 2 DESC LIMIT 200
                                        ) t 
                            GROUP BY user_id) t2
                JOIN  send_prize t1 ON t1.id=t2.maxid
                LEFT JOIN lottery l ON l.id = t1.bet_type
                 LEFT JOIN `user` u ON t1.user_id = u.id
                where tags!=4
                order by 1 desc LIMIT 50;";
        $fetch = $this->db->getConnection()->select($sql);
//        昨日中奖人数+5000，昨日中奖金额
        $yes = date('Y-m-d', strtotime('-1 day'));
        $yes_amount = $this->db->getConnection()->select("select count(distinct user_id) as a_userid from send_prize where money > 0 and created >= '{$yes} 00:00:00' and created <= '{$yes} 23:59:59'");
        $yes_money = $this->db->getConnection()->select("select sum(money) as a_money from send_prize where money > 0 and created >= '{$yes} 00:00:00' and created <= '{$yes} 23:59:59';");
        $data = [
            'content'            => $fetch,
//            'reward_man_total'   => 20000 + SendPrize::count(),//累计中奖人次
            'reward_man_total'   => 126238 + User::count(),//累计会员数
            'reward_money_total' => 22727631 + round(SendPrize::sum('earn_money') / 100),//累计会员盈利
            'reward_man_total_yesterday' => 5000 + $yes_amount[0]->a_userid,//昨日中奖人数
            'reward_money_total_yesterday' => 10000000 + round($yes_money[0]->a_money / 100),//昨日中奖金额
        ];

        $this->redis->setex(\Logic\Define\CacheKey::$perfix['appHomePage'], 86400, json_encode($data));
    }
}