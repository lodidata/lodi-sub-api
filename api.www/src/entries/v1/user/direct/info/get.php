<?php
use Utils\Www\Action;
use Model\User;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取直推活动详情";
    const TAGS = "直推活动";
    const QUERY = [
        "start_date" => "date #开始日期",
        "end_date"   => "date #结束日期",
        "type"       => "int #操作类型（1注册，2充值）"
    ];
    const SCHEMAS = [
        [

        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();

        $dtType = $this->request->getQueryParam('dt_type', 1);   //1全部，2今天，3本周，4上周，5上月
        $type = $this->request->getQueryParam('type', "");       //1注册，2充值，3绑定上级，4提现
        $pageNum = $this->request->getQueryParam('page', 1);
        $pagrSize = $this->request->getQueryParam('page_size', 10);
        $page = ($pageNum - 1) * $pagrSize;
        $today = date("Y-m-d");

        //已获奖励
        $sumAward = DB::connection('slave')
            ->table('direct_record')
            ->where('user_id', $userId)
            ->where('is_transfer', 1)
            ->sum('price');

        //打码量
        $que = DB::connection('slave')
            ->table('direct_record')
            ->where('user_id', $userId)
            ->where('is_transfer', 0);
        $que = !empty($type) ? $que->where('type',$type) : $que;
        if ($dtType == 2) {          //今天
            $que->where('date','=', $today);
        } elseif ($dtType == 3) {    //本周一至今
            $monday = date('Y-m-d', strtotime('Monday this week'));
            $que->where('date', '>=', $monday)->where('date', '<=', $today);
        } elseif ($dtType == 4) {    //上周一至周末
            $pre_monday = date("Y-m-d", strtotime('-2 monday'));
            $pre_sunday = date("Y-m-d", strtotime('-1 sunday'));
            $que->where('date', '>=', $pre_monday)->where('date', '<=', $pre_sunday);
        } elseif ($dtType == 5) {    //上月初至月底
            $start_month = date("Y-m-01", strtotime('-1 month'));
            $end_month = date('Y-m-d',strtotime(date('Y-m-01').' -1 day'));
            $que->where('date', '>=', $start_month)->where('date', '<=', $end_month);
        }
        $balance = $que->get([
            \DB::raw('sum(dml) as dml')
        ])->toArray();

        //钱包余额
        $user = User::where('id', $userId)->first();
        $directBalance = DB::connection('slave')
                        ->table('funds')
                        ->where('id', $user['wallet_id'])
                        ->value('direct_balance');

        $resNum = $res1Num = 0;
        $where = '';
        //直推奖励用户列表详情
        $sql = "SELECT `id`,`username`,`user_id`,`type`,`price`,`date` as `datetime`,`created` as `date` FROM `direct_record` WHERE (`user_id`='{$userId}' OR `sup_uid`='{$userId}')";

        $sqlNum = "SELECT COUNT(*) AS num FROM `direct_record` WHERE (`user_id`='{$userId}' OR `sup_uid`='{$userId}')";

        //提现记录(type=4)从funds_deal_log表获取
        $sql1 = "SELECT `id`, `username`, `user_id`, `deal_money` as `price`, `created` as `date` FROM `funds_deal_log` WHERE `user_id`='{$userId}'";
        $sql1Num = "SELECT COUNT(*) AS num1 FROM `funds_deal_log` WHERE `user_id`='{$userId}'";

        if ($dtType == 2) {          //今天
            $sql .= " AND `date`='{$today}'";
            $sqlNum .= " AND `date`='{$today}'";

            $sql1 .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')='{$today}'";
            $sql1Num .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')='{$today}'";

            $where = " AND `date`='{$today}'";
        } elseif ($dtType == 3) {    //本周一至今
            $monday = date('Y-m-d', strtotime('Monday this week'));
            $sql .= " AND `date`>='{$monday}' AND `date`<='{$today}'";
            $sqlNum .= " AND `date`>='{$monday}' AND `date`<='{$today}'";

            $where = " AND `date`>='{$monday}' AND `date`<='{$today}'";

            $sql1 .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$monday}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$today}'";
            $sql1Num .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$monday}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$today}'";
        } elseif ($dtType == 4) {    //上周一至周末
            $pre_monday = date("Y-m-d", strtotime('-2 monday'));
            $pre_sunday = date("Y-m-d", strtotime('-1 sunday'));
            $sql .= " AND `date`>='{$pre_monday}' AND `date`<='{$pre_sunday}'";

            $where = " AND `date`>='{$pre_monday}' AND `date`<='{$pre_sunday}'";

            $sqlNum .= " AND `date`>='{$pre_monday}' AND `date`<='{$pre_sunday}'";

            $sql1 .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$pre_monday}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$pre_sunday}'";
            $sql1Num .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$pre_monday}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$pre_sunday}'";
        } elseif ($dtType == 5) {    //上月初至月底
            $start_month = date("Y-m-01", strtotime('-1 month'));
            $end_month = date('Y-m-d',strtotime(date('Y-m-01').' -1 day'));
            $sql .= " AND `date`>='{$start_month}' AND `date`<='{$end_month}'";

            $where = " AND `date`>='{$start_month}' AND `date`<='{$end_month}'";

            $sqlNum .= " AND `date`>='{$start_month}' AND `date`<='{$end_month}'";

            $sql1 .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$start_month}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$end_month}'";
            $sql1Num .= " AND DATE_FORMAT(`created`, '%Y-%m-%d')>='{$start_month}' AND DATE_FORMAT(`created`, '%Y-%m-%d')<='{$end_month}'";
        }

        $res1 = $res = [];
        if(in_array($type, [1,2,3])) {
            $sql .= " AND `type`='{$type}' ORDER BY `date` DESC, `id` DESC LIMIT {$page}, {$pagrSize}";
            $res = DB::connection()->select($sql);

            $sqlNum .= " AND `type`='{$type}'";
            $resNum = DB::connection()->select($sqlNum);
        } elseif($type == 4) {
            $sql1 .= " AND `deal_type`=221 ORDER BY `created` DESC, `id` DESC LIMIT {$page}, {$pagrSize}";
            $res1 = DB::connection()->select($sql1);

            $sql1Num .= " AND `deal_type`=221";
            $res1Num = DB::connection()->select($sql1Num);
        } else {
            $sql = "SELECT * FROM (
                        SELECT username,date,type,price FROM direct_record WHERE sup_uid = '{$userId}' or user_id = '{$userId}' {$where}
                    UNION ALL
                        SELECT username,created as date,deal_type as type,deal_money as price FROM funds_deal_log WHERE user_id='{$userId}' AND deal_type=221) B
                    ORDER BY B.date DESC LIMIT {$page}, {$pagrSize}";
            $res = DB::connection()->select($sql);

            $sqlNum = "SELECT COUNT(*) AS num FROM (
                        SELECT username,date,type,price FROM direct_record WHERE sup_uid = '{$userId}' or user_id = '{$userId}' {$where}
                    UNION ALL
                        SELECT username,created as date,deal_type as type,deal_money as price FROM funds_deal_log WHERE user_id='{$userId}' AND deal_type=221) B";
            $resNum = DB::connection()->select($sqlNum);
        }

        $num1 = $res1Num[0]->num1 ?? 0;
        $num = $resNum[0]->num ?? 0;

        $attributes['number'] = (int)$pageNum;
        $attributes['size'] = (int)$pagrSize;
        $attributes['total'] = (int)($num1+$num);

        if(!empty($res)) {
            foreach($res as &$val) {
                switch($val->type) {
                    case 1:
                        $val->type = $this->lang->text("Register");
                        break;
                    case 2:
                        $val->type = $this->lang->text("Direct deposit");
                        break;
                    case 3:
                        $val->type = $this->lang->text("Bind superior");
                        break;
                    default:
                        $val->type = $this->lang->text("Direct withdrawal");
                }
                $val->price = $val->price/100;
            }
        }

        if(!empty($res1)) {
            foreach($res1 as &$val) {
                $val->type = $this->lang->text("Direct withdrawal");
                $val->price = $val->price/100;
            }
        }
        unset($val);

        //按日期降序
        $arr = array_merge($res,$res1);
        if(!empty($arr)) {
            $dateSort = array_column($arr, 'date');
            array_multisort($dateSort, SORT_DESC, $arr);

            foreach($arr as &$v) {
                $v->date = date('Y/m/d', strtotime($v->date));
            }
        }

        $return = [
            'list' => $arr ?? [],
            'balance' => $directBalance/100 ?? 0.00,
            'dml' => $balance[0]->dml/100,
            'awarded' => $sumAward/100 ?? 0.00
        ];

        return $this->lang->set(0, [], $return, $attributes);
    }
};