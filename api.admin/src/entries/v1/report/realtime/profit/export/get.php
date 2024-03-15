<?php

use Logic\Admin\BaseController;

use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '导出会员信息';
    const DESCRIPTION = '导出会员信息';
    
    const STATUS = [
        'on'    =>1, #在线状态
        'off'   =>2, #离线状态
    ];
    const QUERY = [
        'date_start'            => 'datetime(required) #开始日期',
        'date_end'              => 'datetime(required) #结束日期',
        'status'                => "1,2 # 流水操作时间 >< 60分钟离线 当前时间60分钟内在线 在线内所在游戏",
        'user_name '            => "string() #用户名称",
        'balance_start '        => "int() #账户余额 开始",
        'balance_end '          => "int() #账户余额 结束",
        'deposit_amount_start ' => "int() #今日充值 开始",
        'deposit_amount_end  '  => "int() #今日充值 结束",
        'user_amount_start  '   => "int() #今日兑换 开始",
        'user_amount_end  '     => "int() #今日兑换 结束",
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'user_id'                       => '用户ID',
            'user_name'                     => '会员账号',
            'user_level'                    => '会员等级',
            'status'                        => '会员状态',
            'balance'                       => '余额',
            'deposit_user_amount'           => '今日充值',
            'withdrawal_user_amount'        => '今日兑换',
            'bet_user_amount'               => '今日流水',
            'dml'                           => '今日打码量',
            'total_deposit_user_amount'     => '历史总充值',
            'total_withdrawal_user_amount'  => '历史总兑换',
            'total_bet_user_amount'         => '历史总流水',
            'total_dml'               => '历史总打码量',
            'login_ip'                      => 'ip',
            'update_time'                   => '操作时间',
        ],
    ];
    protected $title = [
        'user_id'=>'用户ID','user_name'=>'会员账号','user_level'=>'会员等级','status'=>'会员状态', 'balance' => '余额', 'deposit_user_amount'=>'今日充值','withdrawal_user_amount'=>'今日兑换','bet_user_amount'=>'今日流水',
        'dml'=>'今日打码量', 'total_deposit_user_amount' => '历史总充值', 'total_withdrawal_user_amount' => '历史总兑换',
        'total_bet_user_amount' => '历史总流水', 'total_dml' => '历史总打码量', 'login_ip'=>'ip','update_time'=>'操作时间',
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    public function run() {

        $prarms = $this->params();

        $query = DB::connection('slave')->table('rpt_user')
            ->leftJoin('user','user.id','=','rpt_user.user_id')
            ->leftJoin('user_agent','user_agent.user_id','=','user.id')
            ->leftJoin('funds','user.wallet_id','=','funds.id')
            ->where('rpt_user.count_date','=',$prarms['date_start']);
        if($prarms['user_name']){
            $query ->where('user.name',$prarms['user_name']);
        }
        if ($prarms['status'] == self::STATUS['on']){
            $query ->whereBetween('rpt_user.update_time', [date('Y-m-d H:i:s',time()- 60 * 60) , date('Y-m-d H:i:s',time())]);
        }
        if ($prarms['status'] == self::STATUS['off']){
            $query ->where('rpt_user.update_time', '<=',date('Y-m-d H:i:s',time()- 60 * 60));
        }
        if (isset($prarms['user_level'])){
            $query ->where('user.ranting', $prarms['user_level']);
        }
        if ($prarms['balance_end']){
            $query ->whereBetween('funds.balance',[$prarms['balance_start'] , $prarms['balance_end']]);
        }
        if ($prarms['user_amount_end']){
            $query ->whereBetween('rpt_user.withdrawal_user_amount',[$prarms['user_amount_start'] , $prarms['user_amount_end']]);
        }
        if ($prarms['deposit_amount_end']){
            $query ->whereBetween('rpt_user.deposit_user_amount',[$prarms['deposit_amount_start'] , $prarms['deposit_amount_end']]);
        }
        $data = $query->selectRaw("
            user.id user_id,
            user.name user_name,
            user.ranting user_level,
            TRUNCATE(funds.balance/100, 2) as balance,
            rpt_user.deposit_user_amount,
            rpt_user.withdrawal_user_amount,
            rpt_user.bet_user_amount,
            rpt_user.dml,
            rpt_user.back_user_amount,
            inet6_ntoa(user.login_ip) as login_ip,
            rpt_user.update_time
            ")
            ->orderBy('rpt_user.update_time','desc')
            ->get()->toArray();
        //补充每个用户的游戏盈亏数据
        foreach ($data as &$val) {
            $val = (array)$val;
            $val['status'] = '离线';

            //$val['award_amount'] = \DB::connection('slave')->table('user_monthly_award')->where('user_id',$val['user_id'])->value(DB::raw("sum(award_money) as award_amount")) / 100;
            if (time() - strtotime($val['update_time']) <= 60 * 60){
                $val['status'] = '在线';
                $funds_deal = \DB::connection('slave')->table('funds_deal_log')
                    ->where('user_id',$val['user_id'])
                    ->whereBetween('created', [date('Y-m-d',time()) , date('Y-m-d',time()).' 23:59:59'])
                    ->orderBy('id','desc')
                    ->limit(1)
                    ->get(['memo','deal_type']);
                if (isset($funds_deal[0]->deal_type) && $funds_deal[0]->deal_type == 302) $val['status'] = $funds_deal[0]->memo;
            }

            //历史总投注，历史总充值,历史总彩金，历史总取款, 首充金额，首充时间
            $ls_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('user_id,sum(bet_user_amount) as lsztz,sum(deposit_user_amount) as lszcz,sum(withdrawal_user_amount) as lszqk,sum(dml) as total_dml ')
                ->where('user_id', $val['user_id'])->get()->toArray();
            $val['total_bet_user_amount']           = $ls_rpt_user[0]->lsztz ?? 0;
            $val['total_deposit_user_amount']       = $ls_rpt_user[0]->lszcz ?? 0;
            $val['total_withdrawal_user_amount']    = $ls_rpt_user[0]->lszqk ?? 0;
            $val['total_dml']                       = $ls_rpt_user[0]->total_dml ?? 0;
            $val['first_deposit_user_amount']       = $ls_rpt_user[0]->first_deposit_user_amount ?? 0;
            //盈亏数据
        }
        return $this->exportExcel('userRealtimeReport',$this->title,$data);

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
            foreach ($data as $ke=> $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
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

    function params(){
        $params['page']                 = $this->request->getParam('page',1);
        $params['page_size']            = $this->request->getParam('page_size',20);
        $params['date_start']           = $this->request->getParam('date_start',date('Y-m-d'));
        $params['date_end']             = $this->request->getParam('date_end',date('Y-m-d'));
        $params['status']               = $this->request->getParam('status',0);
        $params['user_level']           = $this->request->getParam('user_level');
        $params['user_name']            = $this->request->getParam('user_name');
        $params['balance_start']        = $this->request->getParam('balance_start') * 100;
        $params['balance_end']          = $this->request->getParam('balance_end') * 100;
        $params['deposit_amount_start'] = $this->request->getParam('deposit_amount_start');
        $params['deposit_amount_end']   = $this->request->getParam('deposit_amount_end');
        $params['user_amount_start']    = $this->request->getParam('user_amount_start');
        $params['user_amount_end']      = $this->request->getParam('user_amount_end');
        return $params;
    }

};
