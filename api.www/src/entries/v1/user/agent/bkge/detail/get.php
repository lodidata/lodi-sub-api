<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查询某一天我的佣金列表";
    const DESCRIPTION = "查询某一天我的佣金列表  \r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "代理返佣";
    const QUERY = [
        "day"           => "date() #日期 2021-08-20",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,20) #分页显示记录数 默认20条记录"
    ];
    const SCHEMAS = [
        [
            "day"           =>"dateTime() #时间 2019-01-15 00:00:00",
            "user_id"       => "int(required) #用户ID",
            "user_name"     => "string(required) #用户名称",
            "bet_amount"    => "int(required) #投注额 214",
            "cur_bkge"      => "int(required) #实际返佣率-- 如10%返回10",
            "bkge"          => "int(required) #返佣金额-- 单位分 345",
            "user_bake"     => "int(required) #返佣次数 12",
            "status"        => "string(required) #是否返佣 1 已返  0 未返",
        ]
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $day = $this->request->getParam('day');
        $user_name = $this->request->getParam('user_name');
         $query = \DB::table('bkge')
            ->where('user_id',$uid)
            ->where('day','>=',$day);
        $user_name && $query->where('bkge_name','=',$user_name);
        $total = clone $query;
        $rsdata = $query->get([
            'day',
            'bkge_uid as user_id',
            'bkge_name as user_name',
            'bet_amount',
            'cur_bkge',
            'bkge',
            'status'
        ])->toArray();
        foreach ($rsdata as &$val){
            $val->bet_amount = intval($val->bet_amount);
            $val->bkge = intval($val->bkge);
        }
        $rs['number'] = $page;
        $rs['size'] = $page_size;
        $rs['total'] = $total->count() ?? 0;
        return $this->lang->set(0, [], $rsdata ?? [], $rs ?? []);
    }
};