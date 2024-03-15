<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 11:29
 */
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '金额统计';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'days' => 'int(required) #多少天',
    ];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($days=7)
    {


        $redis = $this->redis;
        $data = $redis->get('state_amount_'.$days);
        if (empty($data)) {
//            $data   = $this->summary_report($days)??[];
            $data =  $this->summary_report($days);//新报表

        } else {
            $data = json_decode($data, true);
        }
        return $data;
    }


    /*
     * 总报表
     * **/
    public function summary_report($days)
    {

        $date_end =  date('Y-m-d');
        $endDays = $days-1;
        $date_start =  date('Y-m-d',strtotime("-$endDays day"));
        $db = new DB;
        $res = $db->table('day_amount')
            ->where('created','>',$date_start)->where('created','<',$date_end." 23:59:59")
            ->get();
        $summaryData['day'] = $res->pluck('date')->toArray();
        $summaryData['deposit'] = $res->pluck('deposit')->toArray();
        $summaryData['profit'] = $res->pluck('profit')->toArray();
        $summaryData['withdraw'] = $res->pluck('withdraw')->toArray();
        return $summaryData;//启用新的报表

        $summaryData=[];
        $summaryData['day']=$this->lately_day2($days);

        //总存款



        foreach ($this->lately_day($days) as $k=>$v){
//            $v='\''.$v.'\'';

            $sum_money = DB::table('funds_deposit')
                ->whereRaw("status = 'paid' AND `updated`  >='$v'  AND  `updated`  < '$v  23:59:59' ")
                ->sum('money');

            $summaryData['deposit'][$k] = $sum_money ?? 0;
        }

        //$sqls="SELECT `id`,`user_id`,`status`,`money`,`updated`,sum(money) as sums FROM `funds_withdraw`  WHERE  `status`='paid' and DATE_FORMAT(`updated`,'%Y-%m-%d')='2018-03-14'";
        //总取款
        foreach ($this->lately_day($days) as $k=>$v){
//            $v='\''.$v.'\'';
            $sum_money = DB::table('funds_withdraw')
                ->whereRaw("status = 'paid' AND `updated`  >='$v'  AND  `updated`  < '$v  23:59:59' ")
                ->sum('money');

            $summaryData['withdraw'][$k]=$sum_money??0;
        }

        //总输赢
        foreach ($this->lately_day($days) as $k=>$v){
//            $v='\''.$v.'\'';

            $sum_money = DB::table('send_prize')
                ->whereRaw("`status`='normal' AND `updated`  >='$v'  AND  `updated`  < '$v  23:59:59' ")
                ->sum('lose_earn');
            $summaryData['profit'][$k] = $sum_money?? 0;
        }

        $redis->setex('state_amount_'.$days, 300, json_encode($summaryData));
        return $summaryData;
    }

    /*
     *  最近天数日期显示 当前时间开始计算
     * auth:hu
     * param int  days  天数
     * **/
    public function lately_day($days){

        $daysarr=[];
        for ($i=0;$i<$days;$i++){

            $t = time()+3600*8;//这里和标准时间相差8小时需要补足
            $tget = $t-3600*24*$i;//比如5天前的时间
            $daysarr[$i]=date("Y-m-d",$tget);
        }
        sort($daysarr);
        return $daysarr;
    }

    /*
     *  最近天数日期显示 当前时间开始计算
     * auth:hu
     * param int  days  天数
     * **/
    public function lately_day2($days){
        $daysarr=[];
        for ($i=1;$i<$days;$i++){
            $daysarr[$i] = date('Y-m-d',strtotime("-$i day"));
        }

        sort($daysarr);
        return $daysarr;
    }
};
