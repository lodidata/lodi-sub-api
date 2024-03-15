<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查询用户提款记录";
    const DESCRIPTION = "查询用户提款记录,不传时间查全部 \r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "充值提现";
    const QUERY = [
        "start_time" => "date() #查询开始日期 2019-09-12",
        "end_time"   => "date() #查询结束日期  2019-09-12",
        "display"    => "enum[vertical,horizontal](,vertical) #显示方式 vertical垂直,horizontal水平",
        'page'       => "int(,1) #第几页 默认为第1页",
        "page_size"  => "int(,20) #分页显示记录数 默认20条记录"
    ];
    const SCHEMAS = [
        [
            "id" => "int(required) #记录ID",
            "user_id" => "int(required) #用户ID",
            "trade_no" => "string(required) #订单号",
            "money" => "int(required) #申请金额",
            //"fee" => "int(required) #出款手续费",
            // "admin_fee" => "int(required) #行政费",
            "status" => "enum[paid,pending,failed,canceled,rejected](required,pending) #状态(rejected:已拒绝, paid:已支付， pending:待处理，failed：支付失败,canceled:用户取消提款)",
            "created" => "dateTime(required) #创建时间 2019-09-12-12:12:12",
            "updated" => "dateTime(required) #更新时间 2019-09-12-12:12:12",
            "memo" => "string() #说明",
            "confirm_time" => "dateTime(required) #确认时间 2019-09-12-12:12:12",
            "receive_bank_info" => [
                "bank" => "string(required) #银行名称",
                "name" => "string(required) #帐户名",
                "card" => "string(required) #卡号",
                "address" => "string(required) #支行"
            ]
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $page = $this->request->getParam('page', 1);
        $pageSize = $this->request->getParam('page_size', 20);
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $origin = $this->request->getParam('display', 'vertical');
        $type   = $this->request->getParam('type', 1);
        $status = $this->request->getParam('status', 'pending');

        $userId = $this->auth->getUserId();
        $query = \DB::table('funds_withdraw')->where('user_id',$userId)
            ->selectRaw('id,trade_no,money,status,receive_bank_info,memo,confirm_time,created,updated');
        $stime && $query->where('created','>=',$stime);
        $etime && $query->where('created','<=',$etime.' 23:59:59');
        $type  && $query->where('type',$type);
        if(in_array($status, ['paid', 'pending'])==true){
            if( $status == 'paid'){
                $status  && $query->whereIn('status', [
                    'paid',
                    'rejected',
                    'refused',
                    'canceled',
                    'confiscate',
                    'failed'
                ]);
            }else{
                $status  && $query->whereIn('status', ['pending','prepare','obligation','lock','oblock']);
            }
        }
        $total = $query->count();
        $data = $query->forPage($page, $pageSize)->orderBy('id', 'desc')->get()->toArray();
        //获取所有trade_no
        $trade_no_arr = array_column($data,'trade_no');
        //查询所有kpay代付订单
        $kpay_third_id = \DB::table('transfer_config')->where(['code'=>'KPAY','status'=>'enabled'])->value('id');
        $transfer_order = \DB::table('transfer_order')->select(['withdraw_order','transfer_no'])
            ->where('third_id',$kpay_third_id)
            ->whereIn('withdraw_order',$trade_no_arr)
            ->where('transfer_no','<>','')
            ->pluck('transfer_no','withdraw_order')->toArray();
        foreach ($data as &$val) {  //兼容以前的
            if (isset($transfer_order[$val->trade_no])) {
                $val->show_reward = 1;
                $val->transfer_no = $transfer_order[$val->trade_no];
            } else {
                $val->show_reward = 0;
                $val->transfer_no = '';
            }
            if ($origin == 'horizontal') {
                $val->receive_bank_info = json_decode($val->receive_bank_info, true);
                $val->receive_bank_info['card'] = \Model\Profile::getBankCard($val->receive_bank_info['card']);
                $val->type = $this->lang->text("Online withdrawal");
            }
            if ($val->status == 'refused')
                $val->status = 'rejected';
            if ($val->status == 'prepare')
                $val->status = 'paid';
        }
        return $this->lang->set(0, [], $data, [
            'number' => $page, 'size' => $pageSize, 'total' => $total
        ]);
    }
};
