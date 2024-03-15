<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '第三方代付订单列表';
    const DESCRIPTION = '第三方代付';
    
    const QUERY       = [
        'user_name' => 'string() #收款人',
        'status' => 'string() #状态',
        'third_id' => 'int() #第三方代付ID',
        'start_time' => 'string() #交易时间',
        'end_time' => 'string() #交易时间',
        'page' => 'int() #交易时间',
        'page_size' => 'int() #交易时间',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            "id" => 'id',
            "third_id" => '第三方代付ID',
            "trade_no" => '转账订单号',
            "withdraw_order"  => 'funds_withdraw  中的订单号（可有可无）',
            "transfer_no"  => '商户转账编号',
            "money"  => '#申请转账金额',
            "real_money"  => '实际到账金额',
            "fee"  => '#出款手续费',
            "fee_way"  => '1余额扣除，0转账金额中扣除',
            "receive_bank" => '#收款账户银行',
            "receive_bank_code" => '#银行代号',
            "receive_bank_card"  => '#收款账户银行卡号',
            "receive_user_name" => '#收款账户开户名',
            "receive_bank_area"  => '支行名',
            "city_code"  => '银行卡开卡所在城市代号（支行所在城市代号）',
            "tran_type" => 'int()#转账的方式（0：普通  1：加急）',
            "status"  => 'enum[paid,pending,failed]#状态(paid:转账成功， pending:处理中，failed：转账失败)',
            "memo"  => '#转账附言',
            "remark"  => '第三方返回备注',
            "confirm_time"  => '#确认时间',
            "created_uid"  => '#创建用户',
            "created_name"  => '#创建用户',
            "created"  => '新建时',
            "updated"  => '更新时间'
         ],
    ];
//前置方法
    protected $beforeActionList = [
         'verifyToken','authorize'
    ];

    public function run()
    {
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $username = $this->request->getParam('user_name');
        $third = $this->request->getParam('third_id');
        $status = $this->request->getParam('status');
        $page = $this->request->getParam('page',1);
        $size= $this->request->getParam('page_size',20);
        $withdraw_order = $this->request->getParam('withdraw_order', '');
        $transfer_no = $this->request->getParam('transfer_no', '');
        $trade_no = $this->request->getParam('trade_no');   

        $query= \DB::table('transfer_order') ->leftJoin('transfer_config','transfer_order.third_id','=','transfer_config.id');
        $username && $query->where('transfer_order.receive_user_name','=',$username);
        $third && $query->where('transfer_order.third_id','=',$third);
        $status && $query->where('transfer_order.status','=',$status);
        $stime && $query->where('transfer_order.created','>=',$stime . ' 00:00:00');
        $etime && $query->where('transfer_order.created','<=',$etime. ' 59:59:59');
        $withdraw_order && $query->where('transfer_order.withdraw_order','=',$withdraw_order);
        $transfer_no && $query->where('transfer_order.transfer_no','=',$transfer_no);
        $trade_no && $query->where('transfer_order.trade_no', '=', $trade_no);
        $total = clone $query;
        $query = $query->orderBy('id','desc')->forPage($page,$size);
        $attributes['total'] = $total->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        $data = $query->get(['transfer_order.*','transfer_config.name as third_name'])->toArray();
        foreach ($data as &$v){
                $v->city_area = \Model\Area::getAreaInfo($v->city_code)['area'] ?: '';
        }
        return $this->lang->set(0,[],$data,$attributes);
    }

};
