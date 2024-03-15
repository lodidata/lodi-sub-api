<?php

namespace Model;

use DB;

class Dml extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'dml';

    public $timestamps = false;
    //需要添加应有打码量
    const DML_REQUEST = [
        \Model\FundsDealLog::TYPE_REBET,
        \Model\FundsDealLog::TYPE_DAILY_REBET,
        \Model\FundsDealLog::TYPE_WEEKLY_REBET,
        \Model\FundsDealLog::TYPE_MONTHLY_REBET,
        \Model\FundsDealLog::TYPE_LEVEL_MANUAL1,
        \Model\FundsDealLog::TYPE_LEVEL_MANUAL2,
        \Model\FundsDealLog::TYPE_LEVEL_MONTHLY,//不同等级对应的月俸禄奖金
        \Model\FundsDealLog::TYPE_LEVEL_WEEK,//不同等级对应的月俸禄奖金
    ];
    //需要添加实际打码量
    const DML_ACTUAL = [
        \Model\FundsDealLog::TYPE_PAYOUT_LOTTERY,
        \Model\FundsDealLog::TYPE_LOTTERY_SETTLE,
        \Model\FundsDealLog::TYPE_THIRD_SETTLE,
    ];
    public static function boot() {
        parent::boot();
    }

    //添加打码量
    public function addDml($userId, $amount = 0, $money = 0, $memo = "") {
        if ($amount == 0) {
            return true;//打码量为0不记录
        }

        $arr = ['user_id' => $userId, 'withdraw_bet' => $amount, 'money' => $money, 'status' => 1, 'created' => date('Y-m-d H:i:s'), 'start_time' => date('Y-m-d H:i:s'), 'memo' => $memo];

        $id = Dml::insertGetId($arr);

        //更新上一条记录的end_time
        $re = Dml::where('user_id', $userId)
                 ->whereRaw('end_time is null')
                 ->whereRaw("id != $id")
                 ->update(['end_time' => date('Y-m-d H:i:s')]);

        return $re;
    }

    //查询用户打码量列表
    public static function dmlList($userId) {
        return Dml::select('*')
                  ->where('user_id', $userId)
                  ->where('status', 1)
                  ->orderBy('id', 'asc')
                  ->get()
                  ->toArray();
    }

    //更新打码量记录状态
    public static function updDmlStatus($userId, $id) {
        return Dml::where('user_id', $userId)
                  ->whereRaw("id <= $id")
                  ->update(['status' => 0]);
    }

    //查询用户打码量列表
    public static function dmlList4Old($userId) {
        //因老系统之前没有插入打码量表的逻辑故先用该方法，等系统正式切换新框架后运行一段时间可以启用新方法
        $nowTime = date('Y-m-d H:i:s');

        // $lastWithdrawTime = '2017-04-01';  //考虑性能问题默认从4月1号开始
        $sql = " SELECT last_check_time as lasttime FROM `user` WHERE id={$userId}  ";
        $data = DB::select($sql);
        $lastWithdrawTime = "1970-01-01 00:00:00";

        if ($data) {
            $lastWithdrawTime = $data[0]->lasttime;
        }

        //获取人工存提所需打码量
        $sql = "  SELECT withdraw_bet,money,FROM_UNIXTIME(created) as created from `funds_deal_manual`  where user_id={$userId}   and type in (1,5,6) and withdraw_bet > 0 and  created >=UNIX_TIMESTAMP('{$lastWithdrawTime}') and created <UNIX_TIMESTAMP('{$nowTime}') ";
        $cunArr = DB::select($sql);
        if (!$cunArr) {
            $cunArr = [];
        }

        //获取优惠活动所需打码量
        $sql = " SELECT p.withdraw_require as withdraw_bet,p.coupon_money as money,p.created from `active_apply` as p left join active  a on p.active_id = a.id where a.type_id not in(2,3) and p.user_id={$userId} and  p.withdraw_require > 0 and p.status = 'pass' and p.created>= '{$lastWithdrawTime}' and p.created < '{$nowTime}'";
        $activeArr = DB::select($sql);
        if (!$activeArr) {
            $activeArr = [];
        }

        //获取充值所需打码量
        $sql = " SELECT withdraw_bet,process_time as created,(money + ifnull(coupon_money,0)) as money from `funds_deposit` where user_id={$userId} and  withdraw_bet > 0 and status = 'paid' and created >= '{$lastWithdrawTime}' and  created < '{$nowTime}'";
        $rechargeArr = DB::select($sql);
        if (!$rechargeArr) {
            $rechargeArr = [];
        }

        //获取手动增加的打码量
        $sql = " SELECT withdraw_bet, created,0 as money from `dml_manual` where user_id=  {$userId}   and created >= '{$lastWithdrawTime}' ";
        $manualArr = DB::select($sql);
        if (!$manualArr) {
            $manualArr = [];
        }

        $all = [];
        $all = array_merge($cunArr, $activeArr, $rechargeArr, $manualArr);
        if (!$all) {
            return [];
        }
        //排序
        $all = self::array_sort($all, 'created', SORT_ASC);

        //拿到所有数据构建一张虚拟二纬表
        $result = [];
        $all2 = [];

        foreach ($all as $v) {
            $all2[] = $v;
        }

        foreach ($all2 as $k => $v) {
            $v = (array)$v;

            //思路  因为记录中没有end_time这个字段，所以end_time取下一条记录的start_time,如果下一条记录不存在就取当前时间
            if (isset($all2[$k + 1])) {
                $endTime = $all2[$k + 1]->created;
            } else {
                $endTime = date('Y-m-d H:i:s');
            }

            $result[] = ['start_time' => $v['created'], 'withdraw_bet' => $v['withdraw_bet'], 'end_time' => $endTime, 'money' => $v['money']];
        }
        return $result;
    }

    public static function array_sort($array, $on, $order = SORT_DESC) {
        $new_array = [];
        $sortable_array = [];

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                $v = (array)$v;
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[] = $array[$k];
            }
        }

        return $new_array;
    }
}

