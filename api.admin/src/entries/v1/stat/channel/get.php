<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '移动pc渠道占比统计';
    const DESCRIPTION = '';
    
    const QUERY = [
        'days' => 'int(required) #多少天',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($days = 7) {
        $redis = $this->redis;
        $data = $redis->get('state_channel_' . $days);
        if (empty($data)) {
            $data = $this->summary_games($days) ?? [];

            if (!empty($data)) {
                $redis->setex('state_channel_' . $days, 300, json_encode($data,JSON_ERROR_INF_OR_NAN));
            }
        } else {
            $data = json_decode($data, true);
        }
        //  print_r(DB::getQueryLog());exit;
        return $this->lang->set(0, [], $data);
    }

    /*
     * 游戏对比
     */
    public function summary_games($days) {
        $summaryData = [];
        //$sql = "select id,user_id,user_name,origin,bet_num,created,sum(bet_num) FROM `lottery_order` where date_sub(curdate(), INTERVAL 30 DAY) <= date(`created`) GROUP BY origin";

        //移动PC下注占比
        //分别算出几个渠道的下注总数
        $result = DB::table('lottery_order')
                    ->selectRaw('`origin`,count(*) sums')//->whereRaw("date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")
                    ->whereRaw("DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL {$days} DAY) <= created")
                    ->groupBy('origin')
                    ->orderBy('origin')
                    ->get()
                    ->toArray();

        $result = array_map('get_object_vars', $result);
        $total = 0;

        foreach ($result ?? [] as $v) {
            $total += $v['sums'];//统计所有渠道的下注总数
        }

        $pcRate = $h5Rate = $mobileRate = 0;

        //分别算出百分比
        foreach ($result ?? [] as $v) {
            if ($v['origin'] == 1) {
                $pcRate = ($v['sums'] / $total) * 100;
            }

            if ($v['origin'] == 2) {
                $h5Rate = ($v['sums'] / $total) * 100;
            }

            if ($v['origin'] == 3) {
                $mobileRate = ($v['sums'] / $total) * 100;
            }

            $total += $v['sums'];
        }

        $summaryData['channel'][0] = ['pc', 'h5', 'mobile'];
        $summaryData['channel'][1] = [$pcRate, $h5Rate, $mobileRate];
        //print_r($summaryData);exit;

        //游戏下注金额占比
        //分别算出几个游戏的下注金额
        $result2 = DB::table('order_3th')
                     ->selectRaw('`type_name`,`game_name`,sum(money) as sums')//->whereRaw("date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")
                     ->whereRaw("DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL {$days} DAY) <= created")
                     ->groupBy('type_name')
                     ->get()
                     ->toArray();

        if ($result2) {
            $result2 = array_map('get_object_vars', $result2);
        }

        $arrcc = 0;
        //统计所有游戏的下注总金额
        foreach ($result2 ?? [] as $k => $value) {
            $summaryData['orders'][0][] = $value['type_name'];
            $summaryData['orders'][1][] = $value['sums'];
            $arrcc += $value['sums'];
        }

        //  $summaryData['orders'][1][] = DB::table('send_prize')->whereRaw(" date_sub(curdate(), INTERVAL $days DAY) <= date(`created`)")->sum('pay_money');
        $summaryData['orders'][1][] = DB::table('send_prize')
                                        ->whereRaw(" DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL {$days} DAY) <= created")
                                        ->sum('pay_money');

        $arrcc += end($summaryData['orders'][1]);
        $arr3th = [];
        if (isset($summaryData['orders'])) {
            foreach ($summaryData['orders'][1] as $k => $value) {
                $arr3th[$k] = @round($value / $arrcc, 4);//分别算出百分比
            }
        }
        $summaryData['orders'][1] = intval($arr3th);

        return $summaryData;
    }
};
