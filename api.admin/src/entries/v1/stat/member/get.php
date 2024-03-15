<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 11:20
 */

use Logic\Admin\BaseController;
use Logic\Admin\Statistics;

return new class() extends BaseController {
    //    const STATE       = \API::DRAFT;
    const TITLE       = '会员数统计';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'days' => 'int(required) #范围，多少天',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        'data' => ['map'],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($days = 7) {
        $redis = $this->redis;

        $data = $redis->get('state_member_' . $days);

        if ($data) {
            return json_decode($data, true);
        }

        $data = $this->summary_member($days) ?? [];

        if (!empty($data)) {
            $redis->setex('state_member_' . $days, 300, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        // print_r(DB::getQueryLog());  exit;

        return $data;
    }

    /*
     * 会员统计
     * **/
    public function summary_member($days) {
        $logic = new Statistics($this->ci);

        $summaryData = [];
        $summaryData['day'] = $logic->lately_day($days);

        //总会员数
        foreach ($logic->lately_day($days) as $k => $v) {
            $v = date('Y-m-d', strtotime('+1 day', intval($v)));
            $v = '\'' . $v . '\'';

            $count = DB::table('user')//->whereRaw("DATE_FORMAT(`created`,'%Y-%m-%d')<=$v and tags NOT IN (4,7)")
                       ->whereRaw("`created`<=$v and tags NOT IN (4,7)")
                       ->count('*');

            $summaryData['total'][$k] = $count;
        }

        //每天新增加会员数
        foreach ($logic->lately_day($days) as $k => $v) {
            $v = date('Y-m-d', strtotime($v));

            $count = DB::table('user')//->whereRaw("DATE_FORMAT(`created`,'%Y-%m-%d')=$v and tags NOT IN (4,7)")
                       ->whereRaw("`created`>='$v' and `created`<='$v  23:59:59' and tags NOT IN (4,7)")
                       ->count('*');

            $summaryData['newly'][$k] = $count;
        }

        //每天存款会员数
        foreach ($logic->lately_day($days) as $k => $v) {
            $count = DB::table('funds_deposit')
                       ->whereRaw("`status`='paid' AND `updated`  >='$v'  AND  `updated`  < '$v  23:59:59' and money>0")
                       ->count();

            $summaryData['deposit'][$k] = $count;
        }

        //每天下注会员数
        foreach ($logic->lately_day($days) as $k => $v) {
            $end = date("Y-m-d", strtotime($v) + 86400);

            $count = DB::table('lottery_order')
                       ->where('created', '>=', $v)
                       ->where('created', '<', $end)
                       ->count();

            $summaryData['orders'][$k] = $count;
        }

        return $summaryData;
    }
};
