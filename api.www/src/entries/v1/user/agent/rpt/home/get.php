<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "盈亏返佣-首页代理";
    const TAGS = "首页代理";
    const SCHEMAS = [ ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $params = $this->request->getParams();
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $startTime=isset($params['start_time']) ?$params['start_time']:'';
        $endTime=isset($params['end_time'])?$params['end_time']:'';

        $uid = $this->auth->getUserId();
        $query=DB::table('user_agent as ua')
                    ->leftJoin('user as u','u.id','=','ua.user_id')
                    ->where('ua.uid_agent',$uid)
                    ->selectRaw("ua.user_id,u.created,u.last_login,u.state as status,u.name,inet6_ntoa(u.ip) AS ip,inet6_ntoa(u.login_ip) AS last_ip");
        if(!empty($params['name'])){
            $query->where('u.name',$params['name']);
        }
        $total = $query->count();
        $attributes['total'] = $total;
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;


        $res=$query->forPage($page,$page_size)->orderBy('u.created','desc')->get()->toArray();

        if(!empty($res)){
            foreach($res as $val){
                if($val->last_login == 0){
                    $val->last_login='';
                }else{
                    $val->last_login=date('Y-m-d H:i:s',$val->last_login);
                }

                $rptAgentQuery = DB::table('rpt_agent')
                                    ->where('agent_id',$val->user_id);
                if(!empty($startTime)){
                    $rptAgentQuery->where('count_date','>=',$startTime);
                }
                if(!empty($endTime)){
                    $rptAgentQuery->where('count_date','<=',$endTime);
                }
                $rptAgent = $rptAgentQuery->selectRaw("ifnull(sum(deposit_agent_amount),0) as deposit_agent_amount,ifnull(sum(withdrawal_agent_amount),0) as withdrawal_agent_amount,
ifnull(sum(bet_agent_amount - prize_agent_amount),0) as loseearn_amount,ifnull(sum(agent_inc_cnt),0) as agent_cnt")
                                        ->get()[0];

                //自身的输赢数据(盈亏后台会员活跃度，团队报表~~全部改成统计用户的直属下级数据)
                $rptUser = \DB::table('rpt_user')
                                ->select(\DB::raw('ifnull(sum(bet_user_amount), 0) as bet_user_amount'),\DB::raw('ifnull(sum(prize_user_amount), 0) as prize_user_amount'))
                                ->Where('count_date', '>=', $startTime)
                                ->Where('count_date', '<=', $endTime)
                                ->Where('user_id', $val->user_id)
                                ->Where('bet_user_amount', '>', 0)
                                ->get()
                                ->toArray();
                $profit = bcsub($rptUser[0]->bet_user_amount, $rptUser[0]->prize_user_amount, 2);

                $val->deposit_amount=$rptAgent->deposit_agent_amount;
                $val->withdrawal_amount=$rptAgent->withdrawal_agent_amount;
//                $val->loseearn_amount = bcadd($rptAgent->loseearn_amount, $profit, 2);
                $val->loseearn_amount = $profit;
                $val->agent_cnt = $val->user_cnt =$rptAgent->agent_cnt;
            }
        }

        return $this->lang->set(0,[],$res,$attributes);
    }

};