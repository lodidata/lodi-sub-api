<?php

use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '充值留存导出';
    const DESCRIPTION = '';

    const QUERY = [
        'start_date'   => 'datetime(required) #开始日期 默认为当前日期',
        'end_date'     => 'datetime(required) #结束日期 默认为当前日期',
        'field_id'    => "string() #排序字段  date,first_deposit_num ....",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",

    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'date'                 => '日期',
            'first_deposit_num'    => '首充人数',
            'first_deposit_amount' => '首笔充值金额',
            'tomorrow_num'         => '次日复充人数',
            'tomorrow_amount'      => '次日复充金额',
            'tomorrow_rr'          => '次日留存率',
            'tomorrow_avg'         => '次日人均充值',
            'three_num'            => '3日复充人数',
            'three_amount'         => '3日复充金额',
            'three_rr'             => '3日充值留存率',
            'three_avg'            => '3日人均充值',
            'five_num'             => '5日复充人数',
            'five_amount'          => '5日复充金额',
            'five_rr'              => '5日充值留存率',
            'five_avg'             => '5日人均充值',
            'seven_num'            => '7日复充人数',
            'seven_amount'         => '7日复充金额',
            'seven_rr'             => '7日充值留存率',
            'seven_avg'            => '7日人均充值',
            'fif_num'              => '15日复充人数',
            'fif_amount'           => '15日复充金额',
            'fif_rr'               => '15日充值留存率',
            'fif_avg'              => '15日人均充值',
            'thirty_num'           => '30日复充人数',
            'thirty_amount'        => '30日复充金额',
            'thirty_rr'            => '30日充值留存率',
            'thirty_avg'           => '30日人均充值',
        ],
    ];

    protected $title = [
        'date'                 => '日期',
        'agent_name'           => '代理账号',
        'first_deposit_num'    => '首充人数',
        'first_deposit_amount' => '首笔充值金额',
        'old_user_cnt'         => '老充人数',
        'old_user_amount'      => '老充金额',
        'tomorrow_num'         => '次日复充人数',
        'tomorrow_amount'      => '次日复充金额',
        'tomorrow_rr'          => '次日留存率',
        'tomorrow_avg'         => '次日人均充值',
        'three_num'            => '3日复充人数',
        'three_amount'         => '3日复充金额',
        'three_rr'             => '3日充值留存率',
        'three_avg'            => '3日人均充值',
        'five_num'             => '5日复充人数',
        'five_amount'          => '5日复充金额',
        'five_rr'              => '5日充值留存率',
        'five_avg'             => '5日人均充值',
        'seven_num'            => '7日复充人数',
        'seven_amount'         => '7日复充金额',
        'seven_rr'             => '7日充值留存率',
        'seven_avg'            => '7日人均充值',
        'fif_num'              => '15日复充人数',
        'fif_amount'           => '15日复充金额',
        'fif_rr'               => '15日充值留存率',
        'fif_avg'              => '15日人均充值',
        'thirty_num'           => '30日复充人数',
        'thirty_amount'        => '30日复充金额',
        'thirty_rr'            => '30日充值留存率',
        'thirty_avg'           => '30日人均充值',
    ];
    protected $en_title = [
        'date'                 => 'Date',
        'agent_name'           => 'AgentName',
        'first_deposit_num'    => '1stDepMembers',
        'first_deposit_amount' => 'No. of 1st dep.',
        'old_user_cnt'         => 'Repeat depositors',
        'old_user_amount'      => 'Repeat deposit amount',
        'tomorrow_num'         => 'No.of next day Dep',
        'tomorrow_amount'      => 'Next day Dep amount',
        'tomorrow_rr'          => 'percentage of next day Dep',
        'tomorrow_avg'         => 'Average of next day deposit',
        'three_num'            => 'No.of 3days continue Dep',
        'three_amount'         => '3 days continue Dep. amount',
        'three_rr'             => '3 days retention percentage',
        'three_avg'            => '3 days avg. dep.',
        'five_num'             => '5 days continue dep user',
        'five_amount'          => '5 days continue dep amount',
        'five_rr'              => '5 days retention percentage',
        'five_avg'             => '5 days avg. dep.',
        'seven_num'            => '7 days continue dep user',
        'seven_amount'         => '7 days continue dep amount',
        'seven_rr'             => '7 days retention percentage',
        'seven_avg'            => '7 days avg. dep.',
        'fif_num'              => '15 days continue dep user',
        'fif_amount'           => '15 days continue dep amount',
        'fif_rr'               => '15 days retention percentage',
        'fif_avg'              => '15 days avg. dep.',
        'thirty_num'           => '30 days continue dep user',
        'thirty_amount'        => '30 days continue dep amount',
        'thirty_rr'            => '30 days retention percentage',
        'thirty_avg'           => '30 days avg. dep',
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $start_date = $this->request->getParam('start_date',date('Y-m-d'));
        $end_date   = $this->request->getParam('end_date',date('Y-m-d'));
        $field_id   = $this->request->getParam('field_id', 'date');
        $sort_way   = $this->request->getParam('sort_way', 'asc');
        $agent_name = $this->request->getParam('agent_name');
        $user_type = $this->request->getParam('user_type', '');    //用户类型：0-全部，1-新用户，2-老用户
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'desc';

        $start_date_time = strtotime($start_date);
        $end_date_time   = strtotime($end_date);

        if($end_date_time < $start_date_time){
            return $this->lang->set(886, ['结束日期不能小于开始日期']);
        }

        $date_list = [];
        $data_list = [];
        $days = ($end_date_time - $start_date_time) / 86400;

        if($days > 31){
            return $this->lang->set(886, ['查询期间不能大于1个月']);
        }

        for ($i=0;$i <= $days;$i++){
            $date      = date('Y-m-d',strtotime("+{$i} day",strtotime($start_date)));
            $date_list = array_merge($date_list,[$date]);
        }

        foreach ($date_list as $v){
            if(!empty($agent_name)){
                $name=explode(',',$agent_name);
                foreach($name as $item){

                    $itemUserId= \DB::connection('slave')->table('user')
                                    ->where('name','=',$item)
                                    ->first('id');
                    if(empty($itemUserId)){
                        $data = $this->setDefaultData($v);
                        $data->agent_name=$item;
                        array_push($data_list,(array)$data);
                        continue;
                    }

//                    $user_id_list=\DB::connection('slave')->table("child_agent as c")
//                                     ->join("user as u",'c.cid','=','u.id')
//                                     ->where("c.pid",'=',$itemUserId->id)
//                                     ->where('u.first_recharge_time','>=',"{$v} 00:00:00")
//                                     ->where('u.first_recharge_time','<=',"{$v} 23:59:59")
//                                     ->get('c.cid as id')
//                                     ->toArray();

                    $start_time=$v.' 00:00:00';
                    $end_time=$v.' 23:59:59';

                    if ($user_type == 1) {   //新用户
                        $user_list = DB::connection('slave')
                                       ->table('child_agent as c')
                                       ->leftJoin("rpt_user as u",'c.cid','=','u.user_id')
                                       ->selectRaw('u.user_id')
                                       ->whereRaw('u.count_date>=? and u.count_date<=? and u.first_deposit=? and c.pid=? and u.deposit_user_amount >0', [$start_time, $end_time, 1,$itemUserId->id])
                                       ->groupBy(['u.user_id'])->get()->toArray();
                    } elseif ($user_type == 2) {  //老用户
                        $user_list = DB::connection('slave')
                                       ->table('child_agent as c')
                                       ->leftJoin("rpt_user as u",'c.cid','=','u.user_id')
                                       ->selectRaw('u.user_id')
                                       ->whereRaw('u.count_date>=? and u.count_date<=? and u.first_deposit=? and c.pid=? and u.deposit_user_amount >0', [$start_time, $end_time, 0,$itemUserId->id])
                                       ->groupBy(['u.user_id'])->get()->toArray();
                    } else {   //全部
                        $user_list = DB::connection('slave')
                                       ->table('child_agent as c')
                                       ->leftJoin("rpt_user as u",'c.cid','=','u.user_id')
                                       ->selectRaw('u.user_id')
                                       ->whereRaw('u.count_date>=? and u.count_date<=? and c.pid=? and u.deposit_user_amount >0', [$start_time, $end_time,$itemUserId->id])
                                       ->groupBy(['u.user_id'])->get()->toArray();
                    }

                    if(empty($user_list)){
                        $data = $this->setDefaultData($v);
                        $data->agent_name=$item;
                        array_push($data_list,(array)$data);
                        continue;
                    }

                    $user_id_list_arr = array_column($user_list,'user_id');
                    $user_id_list = implode(',',$user_id_list_arr);
                    if(!$user_id_list){
                        $data = $this->setDefaultData($v);
                        $data->agent_name=$item;
                        array_push($data_list,(array)$data);
                        continue;
                    }
                    $prefix     = 'retention:deposit:'.$v.':'.$item.':'.$user_type.":";
                    $redis_key     = $prefix.'1';
                    $data = $this->getData($v, $user_id_list, $redis_key);

                    //老充人数
                    $prefix_key     = 'retention:deposit:old:'.$v.':'.$item.':'.$user_type.':';
                    $old_redis_key     = $prefix_key.'1';
                    $oldData = $this->getOldData($v, $user_id_list_arr, $old_redis_key);
                    $data->old_user_cnt=$oldData->old_user_cnt;
                    $data->old_user_amount=$oldData->old_user_amount;

                    if($user_type == 1){
                        $retention=$data->first_deposit_num;
                    }elseif($user_type == 2){
                        $retention = $data->old_user_cnt;
                    }else{
                        $retention = $data->first_deposit_num + $data->old_user_cnt;
                    }

                    //次日
                    $tomorrow_date = date('Y-m-d',strtotime("+1 day",strtotime($v)));
                    $redis_key     = $prefix.'2';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                    $data->tomorrow_num     = $data2->num;
                    $data->tomorrow_amount  = $data2->amount;
                    $data->tomorrow_rr      = $data2->rr;
                    $data->tomorrow_avg     = $data2->avg;
                    //3日
                    $tomorrow_date = date('Y-m-d',strtotime("+2 day",strtotime($v)));
                    $redis_key     = $prefix.'3';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);
                    $data->three_num    = $data2->num;
                    $data->three_amount = $data2->amount;
                    $data->three_rr     = $data2->rr;
                    $data->three_avg    = $data2->avg;

                    //5日
                    $tomorrow_date = date('Y-m-d',strtotime("+4 day",strtotime($v)));
                    $redis_key     = $prefix.'5';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                    $data->five_num     = $data2->num;
                    $data->five_amount  = $data2->amount;
                    $data->five_rr      = $data2->rr;
                    $data->five_avg     = $data2->avg;

                    //7日
                    $tomorrow_date = date('Y-m-d',strtotime("+6 day",strtotime($v)));
                    $redis_key     = $prefix.'7';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                    $data->seven_num    = $data2->num;
                    $data->seven_amount = $data2->amount;
                    $data->seven_rr     = $data2->rr;
                    $data->seven_avg    = $data2->avg;

                    //15日
                    $tomorrow_date = date('Y-m-d',strtotime("+14 day",strtotime($v)));
                    $redis_key     = $prefix.'15';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                    $data->fif_num      = $data2->num;
                    $data->fif_amount   = $data2->amount;
                    $data->fif_rr       = $data2->rr;
                    $data->fif_avg      = $data2->avg;

                    //30日
                    $tomorrow_date = date('Y-m-d',strtotime("+29 day",strtotime($v)));
                    $redis_key     = $prefix.'30';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                    $data->thirty_num       = $data2->num;
                    $data->thirty_amount    = $data2->amount;
                    $data->thirty_rr        = $data2->rr;
                    $data->thirty_avg       = $data2->avg;
                    $data->agent_name       = $item;

                    array_push($data_list,(array)$data);
                }
            }else{
//                $user_id_sql = "select id from `user` where first_recharge_time >= '{$v} 00:00:00' and first_recharge_time <= '{$v} 23:59:59'";
//                $user_id_list = \DB::connection('slave')->select($user_id_sql);

                $start_time=$v.' 00:00:00';
                $end_time=$v.' 23:59:59';

                if ($user_type == 1) {   //新用户
                    $user_list = DB::connection('slave')->table('rpt_user')
                                   ->selectRaw('user_id')
                                   ->whereRaw('count_date>=? and count_date<=? and first_deposit=? and deposit_user_amount >0', [$start_time, $end_time, 1])->groupBy(['user_id'])->get()->toArray();
                } elseif ($user_type == 2) {  //老用户
                    $user_list = DB::connection('slave')->table('rpt_user')
                                   ->selectRaw('user_id')
                                   ->whereRaw('count_date>=? and count_date<=? and first_deposit=? and deposit_user_amount >0', [$start_time, $end_time, 0])->groupBy(['user_id'])->get()->toArray();
                } else {   //全部
                    $user_list = DB::connection('slave')->table('rpt_user')
                                   ->selectRaw('user_id')
                                   ->whereRaw('count_date>=? and count_date<=? and deposit_user_amount >0', [$start_time, $end_time])->groupBy(['user_id'])->get()->toArray();
                }

                $user_id_list_arr = array_column($user_list,'user_id');
                $user_id_list = implode(',',$user_id_list_arr);
                if(!$user_id_list){
                    $data = $this->setDefaultData($v);
                    array_push($data_list,(array)$data);
                    continue;
                }
                $prefix     = 'retention:deposit:'.$v.':'.$user_type.":";
                $redis_key     = $prefix.'1';
                $data = $this->getData($v, $user_id_list, $redis_key);

                //老充人数
                $prefix_key     = 'retention:deposit:old:'.$v.':'.$user_type.':';
                $old_redis_key     = $prefix_key.'1';
                $oldData = $this->getOldData($v, $user_id_list_arr, $old_redis_key);
                $data->old_user_cnt=$oldData->old_user_cnt;
                $data->old_user_amount=$oldData->old_user_amount;

                if($user_type == 1){
                    $retention=$data->first_deposit_num;
                }elseif($user_type == 2){
                    $retention = $data->old_user_cnt;
                }else{
                    $retention = $data->first_deposit_num + $data->old_user_cnt;
                }

                //次日
                $tomorrow_date = date('Y-m-d',strtotime("+1 day",strtotime($v)));
                $redis_key     = $prefix.'2';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                $data->tomorrow_num     = $data2->num;
                $data->tomorrow_amount  = $data2->amount;
                $data->tomorrow_rr      = $data2->rr;
                $data->tomorrow_avg     = $data2->avg;
                //3日
                $tomorrow_date = date('Y-m-d',strtotime("+2 day",strtotime($v)));
                $redis_key     = $prefix.'3';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);
                $data->three_num    = $data2->num;
                $data->three_amount = $data2->amount;
                $data->three_rr     = $data2->rr;
                $data->three_avg    = $data2->avg;

                //5日
                $tomorrow_date = date('Y-m-d',strtotime("+4 day",strtotime($v)));
                $redis_key     = $prefix.'5';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                $data->five_num     = $data2->num;
                $data->five_amount  = $data2->amount;
                $data->five_rr      = $data2->rr;
                $data->five_avg     = $data2->avg;

                //7日
                $tomorrow_date = date('Y-m-d',strtotime("+6 day",strtotime($v)));
                $redis_key     = $prefix.'7';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                $data->seven_num    = $data2->num;
                $data->seven_amount = $data2->amount;
                $data->seven_rr     = $data2->rr;
                $data->seven_avg    = $data2->avg;

                //15日
                $tomorrow_date = date('Y-m-d',strtotime("+14 day",strtotime($v)));
                $redis_key     = $prefix.'15';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                $data->fif_num      = $data2->num;
                $data->fif_amount   = $data2->amount;
                $data->fif_rr       = $data2->rr;
                $data->fif_avg      = $data2->avg;

                //30日
                $tomorrow_date = date('Y-m-d',strtotime("+29 day",strtotime($v)));
                $redis_key     = $prefix.'30';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $retention, $redis_key);

                $data->thirty_num       = $data2->num;
                $data->thirty_amount    = $data2->amount;
                $data->thirty_rr        = $data2->rr;
                $data->thirty_avg       = $data2->avg;
                $data->agent_name       = '';

                array_push($data_list,(array)$data);
            }

        }
        foreach ($this->en_title as $key => $value){
            $arr[$key] = $this->lang->text($value);
        }

        array_unshift($data_list,$arr);
        if ($this->lang->getLangSet() == 'th'){
            array_unshift($data_list,$this->en_title);
        }
        //默认就是这种排序
        if($field_id == 'date' && $sort_way == 'asc'){
            return $this->exportExcel('deposit_rr_report',$this->title,$data_list);
        }
        //二维数组排序
        $volume = array_column($data_list, $field_id);
        if($sort_way == 'asc')
        {
            array_multisort($volume, SORT_ASC, $data_list);
        }else{
            array_multisort($volume, SORT_DESC, $data_list);
        }
        return $this->exportExcel('deposit_rr_report',$this->title,$data_list);

    }

    public function getData($tomorrow_date, $user_id_list, $redis_key){
        $value = $this->ci->redis->get($redis_key);
        if($value){
            return json_decode($value);
        }

        $date_time          = strtotime(date('Y-m-d'));
        $tomorrow_date_time = strtotime($tomorrow_date);

        //查询时间超过今天 直接返回 0
        if($tomorrow_date_time > $date_time){
            $data2 = (object)[];
            $data2->date                 = $tomorrow_date;
            $data2->first_deposit_num    = 0;
            $data2->first_deposit_amount = '0.00';
            return $data2;
        }
        $sql  = "select  '{$tomorrow_date}' date,count(1) first_deposit_num, ifnull(sum(money),0) first_deposit_amount from funds_deposit where status='paid' and created >= '{$tomorrow_date} 00:00:00' and created <= '{$tomorrow_date} 23:59:59' and FIND_IN_SET('new',state) and user_id in($user_id_list)";
        $data2 = \DB::connection('slave')->select($sql)[0];

        $data2->first_deposit_amount = bcdiv($data2->first_deposit_amount,100,2);

        //查询时间是昨天之前  可以缓存到redis里 缓存时间2天
        if($date_time - $tomorrow_date_time >= 86400){
            $this->ci->redis->setex($redis_key,172800,json_encode($data2));
        }

        return $data2;
    }

    public function getOldData($tomorrow_date, $user_id_list, $redis_key){
        $value = $this->ci->redis->get($redis_key);
        if($value){
            return json_decode($value);
        }

        $date_time          = strtotime(date('Y-m-d'));
        $tomorrow_date_time = strtotime($tomorrow_date);
        $data2 = (object)[];
        $data2->old_user_cnt    = 0;
        $data2->old_user_amount = '0.00';
        $old_user_amount=0;
        //查询时间超过今天 直接返回 0
        if($tomorrow_date_time > $date_time){
            return $data2;
        }

        $data=DB::connection('slave')->table('rpt_user')
                ->selectRaw('user_id,sum( deposit_user_amount ) AS deposit_user_amount')
                ->whereRaw('count_date>=? and count_date<=? and first_deposit=? and deposit_user_amount >0', [$tomorrow_date, $tomorrow_date, 0])
                ->whereIn('user_id',$user_id_list)
                ->groupBy(['user_id'])->get()->toArray();
        if(!empty($data)){
            $data2->old_user_cnt=count($data);
            foreach($data as $item){
                $old_user_amount +=$item->deposit_user_amount;
            }
            $data2->old_user_amount=sprintf('%.2f',$old_user_amount);
        }

        //查询时间是昨天之前  可以缓存到redis里 缓存时间2天
        if($date_time - $tomorrow_date_time >= 86400){
            $this->ci->redis->setex($redis_key,172800,json_encode($data2));
        }

        return $data2;
    }

    public function getData2($tomorrow_date, $user_id_list, $first_deposit_num, $redis_key){
        $value = $this->ci->redis->get($redis_key);
        if($value){
            return json_decode($value);
        }

        $date_time          = strtotime(date('Y-m-d'));
        $tomorrow_date_time = strtotime($tomorrow_date);

        //查询时间超过今天 直接返回 0
        if($tomorrow_date_time > $date_time){
            $data2 = (object)[];
            $data2->num    = 0;
            $data2->amount = '0.00';
            $data2->rr     = 0 ;
            $data2->avg    = '0.00';
            return $data2;
        }

        $sql = "SELECT  count(distinct user_id) num, ifnull(sum(money),0) amount from funds_deposit where status='paid' and created >= '{$tomorrow_date} 00:00:00' and created <= '{$tomorrow_date} 23:59:59' and money>0 and user_id in($user_id_list)";

        $data2 = \DB::connection('slave')->select($sql)[0];
        $data2->amount = bcdiv($data2->amount,100,2);
        $data2->rr     = $first_deposit_num >0 ? bcdiv($data2->num*100, $first_deposit_num,0) : 0;
        $data2->avg    = $data2->num ? bcdiv($data2->amount, $data2->num,2) : 0 ;

        //查询时间是昨天之前  可以缓存到redis里 缓存时间2天
        if($date_time - $tomorrow_date_time >= 86400){
            $this->ci->redis->setex($redis_key,172800,json_encode($data2));
        }

        return $data2;
    }

    public function setDefaultData($date){
        $data = (object)[];
        $data->date                 = $date;
        $data->agent_name           = '';
        $data->first_deposit_num    = 0;
        $data->first_deposit_amount = "0.00";
        $data->tomorrow_num         = 0;
        $data->tomorrow_amount      = "0.00";
        $data->tomorrow_rr          = 0;
        $data->tomorrow_avg         = "0.00";
        $data->three_num            = 0;
        $data->three_amount         = "0.00";
        $data->three_rr             = 0;
        $data->three_avg            = "0.00";
        $data->five_num             = 0;
        $data->five_amount          = "0.00";
        $data->five_rr              = 0;
        $data->five_avg             = "0.00";
        $data->seven_num            = 0;
        $data->seven_amount         = "0.00";
        $data->seven_rr             = 0;
        $data->seven_avg            = "0.00";
        $data->fif_num              = 0;
        $data->fif_amount           = "0.00";
        $data->fif_rr               = 0;
        $data->fif_avg              = "0.00";
        $data->thirty_num           = 0;
        $data->thirty_amount        = "0.00";
        $data->thirty_rr            = 0;
        $data->thirty_avg           = "0.00";
        return $data;
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel;charset=UTF-8');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');
        $content = '';
        foreach ($title as $tval) {
            $content .= $tval . "\t";
        }
        $content .= "\n";
        $keys = array_keys($title);
        if ($data) {
            foreach ($data as $ke => $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;

                $val['tomorrow_rr'] = $val['tomorrow_rr'].'%';
                $val['three_rr']    = $val['three_rr'].'%';
                $val['five_rr']     = $val['five_rr'].'%';
                $val['seven_rr']    = $val['seven_rr'].'%';
                $val['fif_rr']      = $val['fif_rr'].'%';
                $val['thirty_rr']   = $val['thirty_rr'].'%';

                foreach ($keys as $k) {
                    $content .= $val[$k] . "\t";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }
};
