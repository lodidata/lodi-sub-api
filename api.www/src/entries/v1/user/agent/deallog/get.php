<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '团队资金流水记录';
    const DESCRIPTION = " \r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "代理返佣";
    const QUERY = [
        "type"          => "enum[1,2,3](,1) #交易类别：1收入，2支出，3额度转换",
        "game_type"     => "enum(,101) #交易类型:101线上入款，102公司入款，103体育派彩，104彩票派彩，105优惠活动，106手动存款，107返水优惠，108代理退佣，109销售返点，110彩票撤单，111存入代理，112手动增加余额，113手动发放返水，114手动发放优惠，115提款解冻，116追号解冻，117体育撤单, 201会员提款，202彩票下注，203体育下注，204手动提款，205扣除优惠，206代理提款，207手动减少余额，208提款冻结，209追号冻结，210提现扣款，301子转主钱包，302主转子钱包，303手动子转主钱包，304手动主转子钱包，501视讯下注，502视讯派彩，503视讯撤单, 601撤销已经发放的代理退佣",
        "user_name"     => "string() #用户名",
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,20) #分页显示记录数 默认20条记录"
    ];
    const SCHEMAS = [
        [
            'balance'       => "int(required) #主钱包余额",
            'created'       => "string(required) #日志时间",
            'deal_category' => "enum[1,2,3](required) #交易类别：1收入，2支出，3额度转换",
            "deal_category_name" => "string() #交易类别名称",
            'deal_money'    => "int(required)  #交易金额",
            'deal_number'   => "string(required) #流水号",
            'deal_type'     => "enum(required) #交易类型:101线上入款，102公司入款，103体育派彩，104彩票派彩，105优惠活动，106手动存款，107返水优惠，108代理退佣，109销售返点，110彩票撤单，111存入代理，112手动增加余额，113手动发放返水，114手动发放优惠，115提款解冻，116追号解冻，117体育撤单, 201会员提款，202彩票下注，203体育下注，204手动提款，205扣除优惠，206代理提款，207手动减少余额，208提款冻结，209追号冻结，210提现扣款，301子转主钱包，302主转子钱包，303手动子转主钱包，304手动主转子钱包，501视讯下注，502视讯派彩，503视讯撤单, 601撤销已经发放的代理退佣",
            "deal_type_name"=> "string() #交易类型名称",
            'id'            => "int(required) #ID",
            'memo'          => "string() #备注",
            'username'      => "string(required) #用户名"
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $deal_category = $this->request->getParam('type');
        $deal_type = $this->request->getParam('game_type');
        $user_name = $this->request->getParam('user_name');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page',1);
        $size= $this->request->getParam('page_size',20);
        $uid = $this->auth->getUserId();
        $ids = \DB::table('user_agent')->where('user_id','!=',$uid)
            ->where('uid_agent','=',$uid)->pluck('user_id')->toArray();
        $query = DB::table('funds_deal_log')->whereIn('user_id',$ids);
        $deal_category && $query->where('deal_category','=',$deal_category);
        $deal_type && $query->where('deal_type','=',$deal_type);
        $user_name && $query->where('username','=',$user_name);
        $stime && $query->where('created','>=',$stime .' 00:00:00');
        $etime && $query->where('created','<=',$etime .' 59:59:59');
        $query->where('deal_money','>',0);
        $query->whereNotIn('deal_type',[400,401,402,403,405,406]);
        $total = clone $query;
        $attributes['total'] = $total->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        
        $data = $query->forPage($page,$size)->orderby('id', 'desc')->get([
            'balance',
            'created',
            'deal_category',
            'deal_money',
            'deal_number',
            'deal_type',
            'id',
            'memo',
            'username'
        ])->toArray();
        $deal_categorys = [
            1 => $this->lang->text('Income'),
            2 => $this->lang->text('Pay'),
            3 => $this->lang->text('Exchange'),
        ];
        $types              = \Logic\Funds\DealLog::getDealLogTypeFlat();
        foreach ($data as &$datum) {
            $datum->deal_type_name = $types[$datum->deal_type] ?? '';
            $datum->deal_category_name = $deal_categorys[$datum->deal_category] ?? '';
        }
        return $this->lang->set(0,[],$data,$attributes);
    }
};
