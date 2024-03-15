<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查询盈亏返佣记录";
    const DESCRIPTION = "盈亏返佣记录";
    const TAGS = "充值提现";
    const QUERY = [
        "start_time" => "date() #查询开始日期 2019-09-12",
        "end_time"   => "date() #查询结束日期  2019-09-12",
        'page'       => "int(,1) #第几页 默认为第1页",
        "page_size"  => "int(,20) #分页显示记录数 默认20条记录"
   ];
    const SCHEMAS = [
       [
          'deal_log_no' => 'int() #订单号',
          'settle_amount' => 'int() #返佣金额',
          'bkge_time' => 'dateTime() #返佣时间',
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
        $query=DB::table('agent_loseearn_bkge')->where('user_id',$userId)->where('bkge_time','>',0);
        $weekQuery=DB::table('agent_loseearn_week_bkge')->where('user_id',$userId)->where('bkge_time','>',0);
        $monthQuery=DB::table('agent_loseearn_month_bkge')->where('user_id',$userId)->where('bkge_time','>',0);
        $stime && $weekQuery->where('bkge_time','>=',$stime);
        $etime && $weekQuery->where('bkge_time','<=',$etime.' 23:59:59');
        if($stime){
            $query->where('bkge_time','>=',$stime);
            $weekQuery->where('bkge_time','>=',$stime);
            $monthQuery->where('bkge_time','>=',$stime);
        }
        if($etime){
            $query->where('bkge_time','<=',$etime.' 23:59:59');
            $weekQuery->where('bkge_time','<=',$etime.' 23:59:59');
            $monthQuery->where('bkge_time','<=',$etime.' 23:59:59');
        }
        $res=$query->select(['deal_log_no', 'settle_amount', 'bkge_time']);
        $weekRes=$weekQuery->select(['deal_log_no', 'settle_amount', 'bkge_time']);
        $monthRes=$monthQuery->select(['deal_log_no', 'settle_amount', 'bkge_time']);
        $sql=$res->union($weekRes)->union($monthRes);
        $total=$sql->count();
        $total_amount=$sql->sum('settle_amount');
        $data=$sql->forPage($page,$pageSize)->orderBy('bkge_time','desc')->get();

        return $this->lang->set(0, [], $data, [
            'number' => $page, 'size' => $pageSize, 'total' => $total,'total_amount'=>$total_amount
        ]);
    }
};