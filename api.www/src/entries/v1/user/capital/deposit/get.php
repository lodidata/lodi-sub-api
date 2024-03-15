<?php

use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查询充值记录";
    const DESCRIPTION = "充值记录 ,不传时间查全部  \r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "充值提现";
    const QUERY = [
        "start_time" => "date() #查询开始日期 2019-09-12",
        "end_time"   => "date() #查询结束日期  2019-09-12",
        'page'       => "int(,1) #第几页 默认为第1页",
        "page_size"  => "int(,20) #分页显示记录数 默认20条记录"
   ];
    const SCHEMAS = [
       [
          'id' => 'int() #ID',
          'user_id' => 'int() #存款用户ID',
          'trade_no' => '订单号',
          'money' => 'int() #存入金额',
          'coupon_money' => 'int() #优惠金额',
          'pay_type' => 'int() #支付类型(线上(1网银支付)线下(1银行转账)2支付宝3微信4QQ钱包5JD支付)',
           'pay_type_str' => 'string() #支付类型名称',
          'pay_no' => 'string() #支付号(外部/第三方交易号)',
          'name' => 'string() #存款人姓名',
          'recharge_time' => 'string() #交易时间',
          'pay_bank_info' => 'string() #支付账户详情，用以线下支付。json字符串( {"bank":"银行名称", "name":"帐号名", "card":"卡号"} )',
          'receive_bank_account_id' => 'string() #目标账户：线上-第三方账户。线下-银行账户',
          'receive_bank_info' => 'string() #目标账户详情, json字符串，线上：{"id":"渠道ID",“pay":"渠道名", "vender":"支付接口名"}，线下： {"id":"银行ID","bank":"银行名称", "name":"帐号名", "card":"卡号"} ',
          'status' => 'enum[paid,pending,failed,canceled,rejected](,pending) #paid(已支付), pending(待支付), failed(支付失败), canceled(已取消),rejected:已拒绝)',
          'created' => 'dateTime() #创建时间',
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $page = $this->request->getParam('page',1);
        $pageSize = $this->request->getParam('page_size',20);
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');

        $userId = $this->auth->getUserId();
        $query = \DB::table('funds_deposit')->where('user_id',$userId);
        $stime && $query->where('created','>=',$stime);
        $etime && $query->where('created','<=',$etime.' 23:59:59');
        $total = $query->count();
        $data = $query->forPage($page,$pageSize)->orderBy('id','desc')->get()->toArray();
//        1网银支付)线下(1银行转账)2支付宝3微信4QQ钱包5JD支付
        $types = [1=>'银联',2=>'支付宝',3=>'微信',4=>'钱包',5=>'JD'];
        foreach ($data as &$val){
            if(strpos($val->state,'online') === false)
                $val->online = false;
            else
                $val->online = true;
            $val->pay_type_str = $types[$val->pay_type] ?? '手动';
            $val->trade_no = "{$val->trade_no}";
        }

        return $this->lang->set(0, [], $data, [
            'number' => $page, 'size' => $pageSize, 'total' => $total
        ]);
    }
};