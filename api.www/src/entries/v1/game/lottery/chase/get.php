<?php
use Utils\Www\Action;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "GET 彩票追号记录列表";
    const TAGS = "彩票";
    const DESCRIPTION = "彩票追号记录列表   \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";

    const QUERY = [
       "lottery_id" => "int() #彩票id",
       "state"      => "enum[underway,complete,cancel]() #状态(underway:进行中,complete:已结束,cancel=已撤单)",
       "start_time" => "date() #开始时间",
       "end_time"   => "date() #结束时间",
        'page'      => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,20) #分页显示记录数 默认20条记录"
   ];
    const SCHEMAS = [
       [
           "id" => "int() #彩票id",
           "chase_number" => "string() #追号单号",
           "name" => "string() #彩票名称",
           "increment_bet" => "int() #追号总金额",
           "current_bet" => "int() #当期金额",
           "current_amount" => "int() #已追期数",
           "chase_amount" => "int() #总期数",
           "send_money" => "int() #中奖金额",
           "state" => "enum[underway,complete,cancel]() #状态(underway:进行中,complete:已结束,cancel=已撤单)",
           "time" => "int()#追号时间"
       ]
   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $lottery_id = $this->request->getParam('lottery_id');
        $state = $this->request->getParam('state');
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');
        $user_ids = $this->auth->getUserId();
        if($this->auth->getTrialStatus()){
            $query = \Model\LotteryTrialChaseOrder::where('tags','=',7);
            $user_ids && $query->where('user_id',$user_ids);
        }else{
            $query = \Model\LotteryChaseOrder::where('tags','!=',7)->where('user_id',$user_ids);
        }

        if($state){
            $state = $state == 'created' ? 'underway' : $state;
        }
        $lottery_id && $query->where('lottery_id',$lottery_id);
        $state && $query->where('state',$state);
        $start_time && $query->where('created','>=',$start_time);
        $end_time && $query->where('created','<=',$end_time.' 23:59:59');
        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;
        $data = $query->orderBy('id','DESC')->forPage($page,$page_size)->get()->toArray();
        $re = [];
        $states = [
            'complete' => $this->lang->text('complete'),
            'cancel' => $this->lang->text('cancel'),
            'underway' => $this->lang->text('underway'),
        ];
        foreach ($data as $v){
            $tmp['chase_amount'] = $v['sum_periods'];
            $tmp['current_amount'] = $v['complete_periods'];
            $tmp['chase_number'] = (string)$v['chase_number'];
            $tmp['created'] = $v['created'];
            $tmp['increment_bet'] = $v['increment_bet'];
            $tmp['name'] = $v['lottery_name'];
            $tmp['play_name'] = $v['play_group'].'-'.$v['play_name'];
            $tmp['state'] = $states[$v['state']];
            $tmp['state_str'] = $v['state'];
            $re[] = $tmp;
        }
        return $this->lang->set(0,[],$re,$attributes);
    }
};