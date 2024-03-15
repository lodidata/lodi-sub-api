<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取所有消息列表";
    const DESCRIPTION = "\r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "会员消息";
    const QUERY = [
        "type"      => "enum[1,2](,1) #消息类型(1:重要消息,2:一般消息)",
        'page'      => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,10) #分页显示记录数 默认10条记录"
   ];
    const SCHEMAS = [
       [
           "id" => "int() #消息id",
           "status" => "enum[1,0](,0) #状态(1:已读,0:未读)",
           "title" => "string() #消息标题",
           "content" => "string() #消息内容",
           "time" => "string() #时间"
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        if($this->auth->getTrialStatus()) {
            return $this->lang->set(0, [], [], ['number' => 1, 'size' => 10, 'total' => 0]);
        }
        $user_id = $this->auth->getUserId();
        $type     = (int) $this->request->getQueryParam('type', 0);
        $page     = (int) $this->request->getQueryParam('page', 1);
        $pageSize = (int) $this->request->getQueryParam('page_size', 10);
        $id       = (int) $this->request->getQueryParam('id', 0);
        $active   = (int) $this->request->getQueryParam('active', 0);
        $ranting = \Model\User::where('id',$this->auth->getUserId())->value('ranting');
        // 更新消息状态
        if ($id > 0) {
            \Model\MessagePub::where('id', $id)
                ->where('uid', $this->auth->getUserId())
                ->where('type', $type)
                ->update(['status' => 1]);
        }

        $data = DB::table('message_pub')
                    ->leftjoin('message', 'message.id', '=', 'message_pub.m_id')
                    ->where('message_pub.status', '!=', -1);

        if ($id > 0) {
            $data = $data->where('message.id', $id);
        }

        if ($type > 0) {
            $data = $data->where('message.type', $type);
        }
        if ($active == 1) {
            $data = $data->whereIn('message.active_type',[12,13,14]);
        }else{
            $data = $data->whereNotIn('message.active_type',[12,13,14]);
        }
        $data = $data->select([
                    'message_pub.id',
                    'message_pub.status',
                    DB::raw('from_unixtime(message_pub.created) as start_time'),
                    'message.title',
                    'message.content',
                    'message.send_type',
                    'message.type',
                    'message.active_type',
                    'message.active_id',
                    DB::raw('FROM_UNIXTIME(message_pub.created) as start_time'),
                ])
                ->where('message_pub.uid', $this->auth->getUserId())
//                ->orWhereRaw("FIND_IN_SET('LV{$ranting}',message.recipient)")
//                ->orderBy('message_pub.status')
                ->orderBy('message_pub.id', 'DESC')
            ->paginate($pageSize, ['*'], 'page', $page);
        $result = $data->toArray()['data'];
        if($result){
            foreach ($result as &$v){
                $v->title   = $this->translateMsg($v->title);
                $v->content = $this->translateMsg($v->content);
                //如果是彩金消息通知，补充彩金领取信息
                if ($v->active_type == 12 && !empty($v->active_id)) {
                    $handse_log = (array)DB::table("active_handsel_log")->where('active_handsel_id', $v->active_id)->first();
                    if ($handse_log) {
                        $handse_receive = (array)DB::table("handsel_receive_log")->whereRaw('user_id=? and handsel_log_id=?', [$user_id,$handse_log['id']])->first();
                    }
                    $v->give_amount = isset($handse_receive['receive_amount']) ? bcdiv($handse_receive['receive_amount'],100,2) : 0;
                }
                if (isset($handse_receive['status'])) {
                    if ($handse_receive['status'] == 0 && $handse_receive['valid_time'] < date("Y-m-d H:i:s")) {
                        $v->active_status = 2;
                    } else {
                        $v->active_status = $handse_receive['status'];    //-1表示非彩金活动领取状态，0-未领取，1-已领取，2-彩金失效
                    }
                } else {
                    $v->active_status = -1;
                }
                //返水审核
                if($v->active_type == 13 && !empty($v->active_id)){
                    $activeApply=DB::table('active_apply')->where('id',$v->active_id)->first(['status','batch_no']);
                    if(!empty($activeApply)){
                        $check = \DB::table('active_backwater')->where('batch_no',$activeApply->batch_no)->first(['id','type','batch_time']);


                        if ($check){
                            $v->batch_time         = $check->batch_time;
                            $v->backwater_type     = $check-> type;
                            $time = explode('~',$check->batch_time);
                            if ($check-> type ==2){
                                $v->title              = '【'.substr($time[1],5).'】'.$this->lang->text('Weekly rebet money');//周返
                            }else{
                                $v->title              = '【'.substr($time[1],5).'】'.$this->lang->text('Monthly rebet money');//月返
                            }
                        }
                        if($activeApply->status == 'pass'){
                            $v->active_status=1;
                        }elseif($activeApply->status == 'pending'){
                            $v->active_status=0;
                        }
                    }else{
                        $v->active_status = -1;
                    }
                }

                //日返水
                if($v->active_type == 14 && !empty($v->active_id)){
                    $back=DB::table('rebet')->where('batch_no',$v->active_id)->where('user_id',$user_id)->first(['status']);
                    $check          = \DB::table('active_backwater')->where('batch_no',$v->active_id)->first(['batch_time']);
                    if ($check){
                        $v->title  = '【'.substr($check->batch_time,5).'】'.$this->lang->text('Daily rebet money');//日返
                    }
                    if(!empty($back)){
                        if($back->status == 3){
                            $v->active_status=1;
                        }elseif($back->status == 2){
                            $v->active_status=0;
                        }
                    }else{
                        $v->active_status = -1;
                    }
                }
                //月俸禄
//                if($v->active_type == 16 && !empty($v->active_id)){
//                    $back=DB::table('user_monthly_award')->where('id',$v->active_id)->where('user_id',$user_id)->first(['status']);
//                    if(!empty($back)){
//                        if($back->status == 3){
//                            $v->active_status=1;
//                        }elseif($back->status == 2){
//                            $v->active_status=0;
//                        }
//                    }else{
//                        $v->active_status = -1;
//                    }
//                }
                unset($handse_receive);
            }
            unset($v);
        }
        return $this->lang->set(0, [], $result, ['number' => $page, 'size' => $pageSize, 'total' => $data->total()]);
    }
};
