<?php
use Utils\Www\Action;
use Model\MessagePub;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "消息设为已读";
    const DESCRIPTION = "代理消息设为已读";
    const TAGS = "会员消息";
    const PARAMS = [
       "id" => "string () #多个id(1,2,3)"
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $id = $this->request->getParam('id');
        $userId = $this->auth->getUserId();
        /*if (empty($id)) {
            return $this->lang->set(10);
        }*/
        $query = MessagePub::where('type', 1)->where('uid', $this->auth->getUserId());

        if($id){
            $id = explode(',', $id);
            $query->whereIn('id', $id);
        }
        $data = $query->get()->toArray();

        foreach($data as  $val){
            //获取活动类型
            $message_info = DB::table('message')->where('id', $val['m_id'])->first();
            if ($message_info->active_type == 12 && !empty($message_info->active_id)) {
                $handse_log = (array)DB::table("active_handsel_log")->where('active_handsel_id', $message_info->active_id)->first();
                if ($handse_log) {
                    $handse_receive = (array)DB::table("handsel_receive_log")->whereRaw('user_id=? and handsel_log_id=?', [$userId,$handse_log['id']])->first();
                        if ($handse_receive['status'] == 0 && $handse_receive['valid_time'] > date("Y-m-d H:i:s")) {
                            continue;
                        }
                }
            }
            //返水审核
            if($message_info->active_type == 13 && !empty($message_info->active_id)){
                $activeApply=DB::table('active_apply')->where('id',$message_info->active_id)->where('user_id',$userId)->first(['status']);
                if(!empty($activeApply)){
                    if($activeApply->status == 'pending'){
                        continue;
                    }
                }
            }
            if($message_info->active_type == 14 && !empty($message_info->active_id)){
                $back  = DB::table('rebet')->where('batch_no',$message_info->active_id)->where('user_id',$userId)->first(['status']);
                //获取返水明细

                if(!empty($back)){
                    if($back->status == 2){
                        continue;
                    }
                }
            }

            if($message_info->active_type == 16 && !empty($message_info->active_id)){
                $back = DB::table('user_monthly_award')->where('id',$message_info->active_id)->where('user_id',$userId)->first(['status']);
                if($back->status == 2){
                    continue;
                }
            }
            \Model\MessagePub::where('id', $val['id'])->update(['status' => 1, 'updated' => time()]);
            $key        = \Logic\Define\CacheKey::$perfix['backwater'].$val['id'];
            $this->redis->del($key);
        }


        return [];
    }
};