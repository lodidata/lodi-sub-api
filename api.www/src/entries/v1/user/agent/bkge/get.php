<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查询我的佣金";
    const DESCRIPTION = "我的佣金   \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数', 'cur_total' => '当前返佣总金额', 'all_total_bkge' => '累计返佣总金额', 'inferisors_all' => '所有下级代理总数']";
    const TAGS = "代理返佣";
    const QUERY = [
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,10) #分页显示记录数 默认10条记录"
    ];
    const SCHEMAS = [
        [
            "day"           =>"dateTime(required) #时间 2019-01-15 00:00:00",
            "bet_amount"    => "int(required) #投注额 214",
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
        $page_size = $this->request->getParam('page_size',10);
        $stime = $this->request->getParam('start_time') ? : date('Y-m-d',strtotime("-120 day"));
        $etime = $this->request->getParam('end_time') ? $this->request->getParam('end_time').' 23:59:59' : date('Y-m-d H:i:s');
        $query = \DB::table('bkge')
            ->where('user_id',$uid)
            ->where('created','>=',$stime)
            ->where('created','<=',$etime)
            ->groupBy('day');
        $total = clone $query;
        $rsdata = $query->forPage($page,$page_size)->orderBy('id', 'DESC')->get([
            'day',
            \DB::raw('sum(bet_amount) as bet_amount'),
            \DB::raw('sum(bkge) as bkge'),
            \DB::raw('count(1) as user_bake'),
            'status'
        ])->toArray();
        $cur_total = 0;
        foreach ($rsdata as &$val) {
            $val->bkge = intval($val->bkge);
            $val->bet_amount = intval($val->bet_amount);
            $val->user_bake = intval($val->user_bake);
            $cur_total += $val->bkge;
        }
        $t = (array)\DB::table('user_agent')->where('user_id',$uid)->first();
        $rs['cur_total'] = $cur_total;
        $rs['all_total_bkge'] = (int)$t['earn_money'] ?? 0;
        $rs['inferisors_all'] = (int)$t['inferisors_all'] ?? 0;
        $rs['number'] = $page;
        $rs['size'] = $page_size;
        $rs['total'] = $total->pluck(\DB::raw('count(1) as total'))->count();
        return $this->lang->set(0, [], $rsdata ?? [], $rs ?? []);
    }
};