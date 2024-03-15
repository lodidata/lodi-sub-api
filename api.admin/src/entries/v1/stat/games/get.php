<?php
/**
 * vegas2.0
 *
 * @auth      *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date      2017/4/10 13:32
 */

use Logic\Admin\BaseController;
use Model\Admin\LotteryChase;
return new class() extends BaseController
{
	
//	const STATE       = \API::DRAFT;
	const TITLE       = '游戏下单数、金额、盈亏统计';
	const DESCRIPTION = '[ map # orders 订单数，amount 金额数， profit 盈亏数';
	
	const QUERY       = [ 'days' => 'int(required) #多少天', ];
	
	const PARAMS      = [];
	const SCHEMAS     = [];
	
//	public $days;
//前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
	
	public function run($days=7) {

	    $redis = $this->redis;
        $data = $redis->get('state_games_'.$days);
        if (empty($data)) {
            $rs = $this->game_count($days);
        }else{
            $rs = json_decode($data,true);
        }
        return $rs;
	}

    /*
     *  游戏统计
     *   auth hu
     *   param int days
     * ***/
    public function game_count($days)
    {
        $summaryData = [];
        //游戏下注金额占比
        //amount下注金额


//        $sql=$this->getHelper()->select('order_3th')
//            ->fields('`id`,`user_id`,`user_name`,`type_name`,`game_name`,`money`,`created`,sum(`money`) as sums,count(*) as con,SUM(`win_loss`) as win_loss')
//            ->where("date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")
//            ->groupBy('type_name')
//            ->sql();


        $result = DB::table('order_3th')
            ->selectRaw('id,user_id,user_name,type_name,game_name,money,created,sum(money) as sums,count(*) as con,SUM(win_loss) as win_loss')
//            ->whereRaw("date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")
            ->whereRaw("DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL {$days} DAY) <= created")
            ->groupBy('type_name')
            ->get()
            ->toArray();
//        print_r(DB::getQueryLog());exit;
        $result = array_map('get_object_vars',$result);
        $arrcc=0;
        foreach ($result ?? []  as $k=>$value){

            $summaryData['orders']['amount'][0][]=$value['type_name'];   //amount下注金额
            $summaryData['orders']['amount'][1][]=$value['sums'];

            $summaryData['orders']['orders'][0][]=$value['type_name'];  //orders下单数
            $summaryData['orders']['orders'][1][]=$value['con'];

            $summaryData['orders']['profit'][0][]=$value['type_name'];  //profit盈亏金额
            $summaryData['orders']['profit'][1][]=$value['win_loss'];

            $arrcc+=$value['sums'];
        }
//        $sql = "SELECT sum(pay_money) as pay_money, count(*) as count, sum(lose_earn) as lose_earn  FROM send_prize where date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)";
//        $rs = $db->row($sql);
        $rs = DB::table('send_prize')
                ->selectRaw('sum(pay_money) as pay_money, count(*) as count, sum(lose_earn) as lose_earn')
//                ->whereRaw("date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")
                ->whereRaw("DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL {$days} DAY) <= created")
                ->get()
                ->toArray();
        $rs = (array) $rs[0];

        $summaryData['orders']['amount'][0][]='彩票';   //amount下注金额
        $summaryData['orders']['amount'][1][]=$rs['pay_money'];

        $summaryData['orders']['orders'][0][]='彩票';  //orders下单数
        $summaryData['orders']['orders'][1][]=$rs['count'];

        $summaryData['orders']['profit'][0][]='彩票';  //profit盈亏金额
        $summaryData['orders']['profit'][1][]=$rs['lose_earn'];
        $this->redis->setex('state_games_'.$days,'300',json_encode($summaryData));
        return $summaryData;
    }

};
