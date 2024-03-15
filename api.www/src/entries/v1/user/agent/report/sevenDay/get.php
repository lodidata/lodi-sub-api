<?php
use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "近七日数据";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [

    ];
    const SCHEMAS = [
        [
            "bet_amount"    => "int(required) #流水金额",
            "bkge"          => "int(required) #净盈利",
        ]
    ];

    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();

        $res = [];
        //获取近七天日期
        $queryDate = [];
        for ($i=1; $i<=7; $i++) {
            $day = date("Y-m-d", strtotime("-{$i} days"));
            array_push($queryDate, $day);
            $res[$day] = [];
        }

        $start = date("Y-m-d", strtotime("-7 days"));
        $end = date("Y-m-d", strtotime("-1 days"));
        //查询用户近七天的流水,盈利(返佣金额)
        $info = DB::table("unlimited_agent_bkge")->select(['date','bet_amount','bkge'])
            ->whereRaw('uid = :uid and date >= :start and date <= :end', ['uid'=>$uid, 'start'=>$start, 'end'=>$end])->get()->toArray();
        if (!empty($info)) {
           foreach ($info as $item) {
               $res[$item->date]['bet_amount'] = $item->bet_amount;
               $res[$item->date]['bkge'] = $item->bkge;
           }
        }


        return $this->lang->set(0, [], $res);
    }
};