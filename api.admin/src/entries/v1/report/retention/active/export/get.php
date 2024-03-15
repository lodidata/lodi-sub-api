<?php

use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '活跃留存导出';
    const DESCRIPTION = '';

    const QUERY = [
        'start_date'   => 'datetime(required) #开始日期 默认为当前日期',
        'end_date'     => 'datetime(required) #结束日期 默认为当前日期',
        'field_id'    => "string() #排序字段  date,active_num,active_amount....",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",

    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'date'                 => '日期',
            'active_num'           => '活跃人数',
            'active_amount'        => '总流水',
            'active_amount_avg'    => '人均流水',
            'tomorrow_num'         => '次日活跃人数',
            'tomorrow_amount'      => '次日总流水',
            'tomorrow_rr'          => '次日留存率',
            'tomorrow_avg'         => '次日人均流水',
            'three_num'            => '3日活跃人数',
            'three_amount'         => '3日总流水',
            'three_rr'             => '3日充值留存率',
            'three_avg'            => '3日人均流水',
            'five_num'             => '5日活跃人数',
            'five_amount'          => '5日总流水',
            'five_rr'              => '5日充值留存率',
            'five_avg'             => '5日人均流水',
            'seven_num'            => '7日活跃人数',
            'seven_amount'         => '7日总流水',
            'seven_rr'             => '7日充值留存率',
            'seven_avg'            => '7日人均流水',
            'fif_num'              => '15日活跃人数',
            'fif_amount'           => '15日总流水',
            'fif_rr'               => '15日充值留存率',
            'fif_avg'              => '15日人均流水',
            'thirty_num'           => '30日活跃人数',
            'thirty_amount'        => '30日总流水',
            'thirty_rr'            => '30日充值留存率',
            'thirty_avg'           => '30日人均流水',
        ],
    ];

    protected $title = [
        'date'                 => '日期',
        'agent_name'           => '代理账号',
        'active_num'           => '活跃人数',
        'active_amount'        => '总流水',
        'active_amount_avg'    => '人均流水',
        'tomorrow_num'         => '次日活跃人数',
        'tomorrow_amount'      => '次日总流水',
        'tomorrow_rr'          => '次日留存率',
        'tomorrow_avg'         => '次日人均流水',
        'three_num'            => '3日活跃人数',
        'three_amount'         => '3日总流水',
        'three_rr'             => '3日充值留存率',
        'three_avg'            => '3日人均流水',
        'five_num'             => '5日活跃人数',
        'five_amount'          => '5日总流水',
        'five_rr'              => '5日充值留存率',
        'five_avg'             => '5日人均流水',
        'seven_num'            => '7日活跃人数',
        'seven_amount'         => '7日总流水',
        'seven_rr'             => '7日充值留存率',
        'seven_avg'            => '7日人均流水',
        'fif_num'              => '15日活跃人数',
        'fif_amount'           => '15日总流水',
        'fif_rr'               => '15日充值留存率',
        'fif_avg'              => '15日人均流水',
        'thirty_num'           => '30日活跃人数',
        'thirty_amount'        => '30日总流水',
        'thirty_rr'            => '30日充值留存率',
        'thirty_avg'           => '30日人均流水',
    ];
    protected $en_title = [
        'date'                 => 'Date',
        'agent_name'           => 'AgentName',
        'active_num'           => 'Active user',
        'active_amount'        => 'Total turnover',
        'active_amount_avg'    => 'Average turnover',
        'tomorrow_num'         => 'Next day Active user',
        'tomorrow_amount'      => 'Next day total turnover',
        'tomorrow_rr'          => 'percentage of next day Dep',
        'tomorrow_avg'         => 'Average of next day turnover',
        'three_num'            => '3 days active user',
        'three_amount'         => '3 days total turnover',
        'three_rr'             => '3 days dep retention rate',
        'three_avg'            => '3 days avg. turnover',
        'five_num'             => '5 days active user',
        'five_amount'          => '5 days total turnover',
        'five_rr'              => '5 days dep retention rate',
        'five_avg'             => '5 days avg. turnover',
        'seven_num'            => '7 days active user',
        'seven_amount'         => '7 days total turnover',
        'seven_rr'             => '7 days dep retention rate',
        'seven_avg'            => '7 days avg. turnover',
        'fif_num'              => '15 days active user',
        'fif_amount'           => '15 days total turnover',
        'fif_rr'               => '15 days dep retention rate',
        'fif_avg'              => '15 days avg. turnover',
        'thirty_num'           => '30 days active user',
        'thirty_amount'        => '30 days total turnover',
        'thirty_rr'            => '30 days dep retention rate',
        'thirty_avg'           => '30 days avg. turnover',
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
                    $user_id_list=\DB::connection('slave')->table("rpt_user")
                                     ->where("count_date",'=',$v)
                                     ->where("bet_user_amount",'>',0)
                                     ->where("superior_id",'=',$itemUserId->id)
                                     ->get('user_id')
                                     ->toArray();
                    if(empty($user_id_list)){
                        $data = $this->setDefaultData($v);
                        $data->agent_name=$item;
                        array_push($data_list,(array)$data);
                        continue;
                    }

                    $user_id_list = array_column($user_id_list,'user_id');
                    array_unshift($user_id_list,$itemUserId->id);
                    $user_id_list = implode(',',$user_id_list);



                    $prefix     = 'retention:active:'.$v.':'.$item.':';
                    $redis_key  = $prefix.'1';
                    $data = $this->getData($v, $user_id_list, $redis_key);

                    //次日
                    $tomorrow_date = date('Y-m-d',strtotime("+1 day",strtotime($v)));
                    $redis_key     = $prefix.'2';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                    $data->tomorrow_num     = $data2->num;
                    $data->tomorrow_amount  = $data2->amount;
                    $data->tomorrow_rr      = $data2->rr;
                    $data->tomorrow_avg     = $data2->avg;
                    //3日
                    $tomorrow_date = date('Y-m-d',strtotime("+2 day",strtotime($v)));
                    $redis_key     = $prefix.'3';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);
                    $data->three_num    = $data2->num;
                    $data->three_amount = $data2->amount;
                    $data->three_rr     = $data2->rr;
                    $data->three_avg    = $data2->avg;

                    //5日
                    $tomorrow_date = date('Y-m-d',strtotime("+4 day",strtotime($v)));
                    $redis_key     = $prefix.'5';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                    $data->five_num     = $data2->num;
                    $data->five_amount  = $data2->amount;
                    $data->five_rr      = $data2->rr;
                    $data->five_avg     = $data2->avg;

                    //7日
                    $tomorrow_date = date('Y-m-d',strtotime("+6 day",strtotime($v)));
                    $redis_key     = $prefix.'7';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                    $data->seven_num    = $data2->num;
                    $data->seven_amount = $data2->amount;
                    $data->seven_rr     = $data2->rr;
                    $data->seven_avg    = $data2->avg;

                    //15日
                    $tomorrow_date = date('Y-m-d',strtotime("+14 day",strtotime($v)));
                    $redis_key     = $prefix.'15';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                    $data->fif_num      = $data2->num;
                    $data->fif_amount   = $data2->amount;
                    $data->fif_rr       = $data2->rr;
                    $data->fif_avg      = $data2->avg;

                    //30日
                    $tomorrow_date = date('Y-m-d',strtotime("+29 day",strtotime($v)));
                    $redis_key     = $prefix.'30';
                    $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                    $data->thirty_num       = $data2->num;
                    $data->thirty_amount    = $data2->amount;
                    $data->thirty_rr        = $data2->rr;
                    $data->thirty_avg       = $data2->avg;
                    $data->agent_name       = $item;

                    array_push($data_list,(array)$data);
                }
            }else{
                $user_id_sql = "select user_id from `rpt_user` where count_date = '{$v}' and bet_user_amount > 0";
                $user_id_list = \DB::connection('slave')->select($user_id_sql);

                $user_id_list = array_column($user_id_list,'user_id');
                $user_id_list = implode(',',$user_id_list);

                if(!$user_id_list){
                    $data = $this->setDefaultData($v);
                    array_push($data_list,(array)$data);
                    continue;
                }

                $prefix     = 'retention:active:'.$v.':';
                $redis_key  = $prefix.'1';
                $data = $this->getData($v, $user_id_list, $redis_key);

                //次日
                $tomorrow_date = date('Y-m-d',strtotime("+1 day",strtotime($v)));
                $redis_key     = $prefix.'2';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                $data->tomorrow_num     = $data2->num;
                $data->tomorrow_amount  = $data2->amount;
                $data->tomorrow_rr      = $data2->rr;
                $data->tomorrow_avg     = $data2->avg;
                //3日
                $tomorrow_date = date('Y-m-d',strtotime("+2 day",strtotime($v)));
                $redis_key     = $prefix.'3';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);
                $data->three_num    = $data2->num;
                $data->three_amount = $data2->amount;
                $data->three_rr     = $data2->rr;
                $data->three_avg    = $data2->avg;

                //5日
                $tomorrow_date = date('Y-m-d',strtotime("+4 day",strtotime($v)));
                $redis_key     = $prefix.'5';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                $data->five_num     = $data2->num;
                $data->five_amount  = $data2->amount;
                $data->five_rr      = $data2->rr;
                $data->five_avg     = $data2->avg;

                //7日
                $tomorrow_date = date('Y-m-d',strtotime("+6 day",strtotime($v)));
                $redis_key     = $prefix.'7';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                $data->seven_num    = $data2->num;
                $data->seven_amount = $data2->amount;
                $data->seven_rr     = $data2->rr;
                $data->seven_avg    = $data2->avg;

                //15日
                $tomorrow_date = date('Y-m-d',strtotime("+14 day",strtotime($v)));
                $redis_key     = $prefix.'15';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                $data->fif_num      = $data2->num;
                $data->fif_amount   = $data2->amount;
                $data->fif_rr       = $data2->rr;
                $data->fif_avg      = $data2->avg;

                //30日
                $tomorrow_date = date('Y-m-d',strtotime("+29 day",strtotime($v)));
                $redis_key     = $prefix.'30';
                $data2 = $this->getData2($tomorrow_date, $user_id_list, $data->active_num, $redis_key);

                $data->thirty_num       = $data2->num;
                $data->thirty_amount    = $data2->amount;
                $data->thirty_rr        = $data2->rr;
                $data->thirty_avg       = $data2->avg;
                $data->agent_name       = '';

                array_push($data_list,(array)$data);
            }

        }
        global $app;
        $ci = $app->getContainer();

        foreach ($this->en_title as $key => $value){
            $arr[$key] = $this->lang->text($value);
        }

        array_unshift($data_list,$arr);
        if ($this->lang->getLangSet() == 'th'){
            array_unshift($data_list,$this->en_title);
        }

        //默认就是这种排序
        if($field_id == 'date' && $sort_way == 'asc'){
            return $this->exportExcel('deposit_active_report',$this->title,$data_list);
        }
        //二维数组排序
        $volume = array_column($data_list, $field_id);
        if($sort_way == 'asc')
        {
            array_multisort($volume, SORT_ASC, $data_list);
        }else{
            array_multisort($volume, SORT_DESC, $data_list);
        }

        return $this->exportExcel('deposit_active_report',$this->title,$data_list);

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
            $data2->date              = $tomorrow_date;
            $data2->active_num        = 0;
            $data2->active_amount     = '0.00';
            $data2->active_amount_avg = '0.00';
            return $data2;
        }
        $sql  = "select  '{$tomorrow_date}' date,count(1) active_num, ifnull(sum(bet_user_amount),0) active_amount from rpt_user where count_date = '{$tomorrow_date}'  and user_id in($user_id_list)";
        $data2 = \DB::connection('slave')->select($sql)[0];

        $data2->active_amount_avg = $data2->active_num ? bcdiv($data2->active_amount, $data2->active_num ,2) : '0.00';

        //查询时间是昨天之前  可以缓存到redis里 缓存时间2天
        if($date_time - $tomorrow_date_time >= 86400){
            $this->ci->redis->setex($redis_key,172800,json_encode($data2));
        }

        return $data2;
    }

    public function getData2($tomorrow_date, $user_id_list, $firstNum, $redis_key){
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

        $sql = "SELECT  count(*) num, ifnull(sum(bet_user_amount),0) amount from rpt_user where count_date = '{$tomorrow_date}' and bet_user_amount > 0 and user_id in($user_id_list)";

        $data2 = \DB::connection('slave')->select($sql)[0];
        $data2->amount = $data2->amount;
        $data2->rr     = bcdiv($data2->num*100, $firstNum,0);
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
        $data->active_num           = 0;
        $data->active_amount        = "0.00";
        $data->active_amount_avg    = '0.00';
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
        header('Content-type:application/vnd.ms-excel');
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
