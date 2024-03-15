<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取代理返佣汇总列表";
    const TAGS = "个人中心";
    const QUERY = [
        "date"      => "date #日期",
        "user_name" => "user_name #用户名称",
        "type"      => "type #游戏类型",
    ];
    const SCHEMAS = [
        [

        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();

//        $date      = $this->request->getQueryParam('date');
        $start_time    = $this->request->getQueryParam('start_time','');
        $end_time      = $this->request->getQueryParam('end_time');
        $userName  = $this->request->getQueryParam('user_name', "");
        $type      = $this->request->getQueryParam('type', "");            //空为全部
        $page      = (int) $this->request->getQueryParam('page', 1);
        $pageSize  = (int) $this->request->getQueryParam('page_size', 20);

        $query = DB::table("agent_bkge_log")->where('agent_id', $userId)->where('user_id', '!=', $userId);
        if(empty($type)) {
            $query = $query->groupBy('user_id'); 
        }

        if(!empty($userName)) {
            $query = $query->where('user_name', 'like', "%".$userName."%");
        }
        if(!empty($type)) {
            $query = $query->where('game_type', $type);
        }
//        if(!empty($date)) {
//            $query = $query->where('date', $date);
//        }
        if(!empty($start_time)){
            $query = $query->where('date', '>=', $start_time);
        }
        if(!empty($end_time)){
            $query = $query->where('date', '<=', $end_time);
        }

        if(empty($type)) {
            $attributes['total'] = $query->get([\DB::raw('count(id) as count')])->count();
        } else {
            $attributes['total'] = $query->count();
        }

        $attributes['number'] = $page;
        $attributes['size'] = $pageSize;

        if(empty($type)) {
            $data = $query->orderBy('date','DESC')
                          ->forPage($page, $pageSize)
                          ->get([
                              'user_id',
                              'user_name',
                              'date',
                              \DB::raw('sum(bkge_money) bkge_money')
                          ])
                          ->toArray();
        } else {
            $data = $query->orderBy('date','DESC')
                          ->forPage($page, $pageSize)
                          ->get(['user_id', 'user_name', 'date', 'bkge_money'])
                          ->toArray();
        }

        foreach($data as $val) {
            $val->bkge_money = intval($val->bkge_money);
        }

        $sumQuery = DB::table("agent_bkge_log")
                         ->where('agent_id', $userId)
                         ->where('user_id', '!=', $userId);
        if(!empty($type)) {
            $sumQuery = $sumQuery->where('game_type', $type);
        }
        if(!empty($userName)) {
            $sumQuery = $sumQuery->where('user_name', 'like', "%".$userName."%");
        }
        if(!empty($start_time)){
            $sumQuery = $sumQuery->where('date', '>=', $start_time);
        }
        if(!empty($end_time)){
            $sumQuery = $sumQuery->where('date', '<=', $end_time);
        }
        $sum = $sumQuery->get([
                            \DB::raw('sum(bkge_money) bkge_money')
                        ])->toArray();
        $attributes['total_bkge'] = $sum[0]->bkge_money ?? 0;

        return $this->lang->set(0, [], $data, $attributes);
    }
};