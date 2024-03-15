<?php
use Utils\Www\Action;
use Model\MessagePub;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "删除消息接口";
    const DESCRIPTION = "删除消息接口";
    const TAGS = "会员消息";
    const QUERY = [
        "id" => "string() #消息id[删除多条](1,2,3)"
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $id = $this->request->getQueryParam('id');

        $userId = $this->auth->getUserId();
        $query = \Model\MessagePub::where('uid', $userId);
        if($id){
            $id = explode(',', $id);
            $query->whereIn('id', $id);
        }
        $data = $query->get()->toArray();
        $is_remine = false;//判断是否提示
        $this->db->getConnection()->beginTransaction();
        try {
            foreach($data as $key => $val){
                if ($val['status'] == 0){
                    return createRsponse($this->response, 200, -2, $this->lang->text(4207));
                }
                //未领取的消息不给删除
                //获取活动类型
                $message_info = DB::table('message')->where('id', $val['m_id'])->first();
                if ($message_info->active_type == 12 && !empty($message_info->active_id)) {
                    $handse_log = (array)DB::table("active_handsel_log")->where('active_handsel_id', $message_info->active_id)->first();
                    if ($handse_log) {
                        $handse_receive = (array)DB::table("handsel_receive_log")->whereRaw('user_id=? and handsel_log_id=?', [$userId,$handse_log['id']])->first();
                        if ($handse_receive['status'] == 0 && $handse_receive['valid_time'] > date("Y-m-d H:i:s")) {
                                $is_remine = true;
                            continue;
                        }
                    }
                }
                //返水审核
                if($message_info->active_type == 13 && !empty($message_info->active_id)){
                    $activeApply=DB::table('active_apply')->where('id',$message_info->active_id)->where('user_id',$userId)->first(['status']);
                    if(!empty($activeApply)){
                        if($activeApply->status == 'pending'){
                            $is_remine = true;
                            continue;
                        }
                    }
                }
                if($message_info->active_type == 14 && !empty($message_info->active_id)){
                    $back  = DB::table('rebet')->where('batch_no',$message_info->active_id)->where('user_id',$userId)->first(['status','rebet']);
                    //获取返水明细

                    if(!empty($back)){
                        if($back->status == 2){
                            $is_remine = true;
                            continue;
                        }
                    }
                }
                if($message_info->active_type == 16 && !empty($message_info->active_id)){
                    $back = DB::table('user_monthly_award')->where('id',$message_info->active_id)->where('user_id',$userId)->first(['status','award_money']);
                    if($back->status == 2){
                        $is_remine = true;
                        continue;
                    }
                }
                \Model\MessagePub::where('id', $val['id'])->delete();
                $key        = \Logic\Define\CacheKey::$perfix['backwater'].$val['id'];
                $this->redis->del($key);
            }
            if($is_remine){
                return $this->lang->set(4205);
            }
            $this->db->getConnection()->commit();
        }catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }

        return [];
    }
};