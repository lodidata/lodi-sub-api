<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "操作申请代理";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $params = $this->request->getParams();
        $id = $params['id'] ?? 0;
        $status = $params['status'] ?? 0;
        $reply = $params['reply'] ?? '';    //回复用户
        $reply = trim($reply);
        $remark = $params['remark'] ?? '';    //备注
        $is_up = $params['is_up'] ?? 0;    //是否修改备注：1-是，0-否

        if(!in_array($status, [1,2])){
            return $this->lang->set(-2);
        }

        //检查数据是否存在
        try {
            $this->db->getConnection()->beginTransaction();

            if($is_up == 0){
                $apply_info = \DB::table('agent_apply')->where('status', 0)->lockForUpdate()->find($id);
                if (empty($apply_info)) {
                    $this->db->getConnection()->rollback();
                    return $this->lang->set(-2);
                }
                
                $update = [
                    'status'       => $status,
                    'remark'       => $remark,
                    'reply'        => $reply,
                    'operate_time' => date('Y-m-d H:i:s'),
                    'operate_uid'  => $userId,
                    'deal_user'    => 2,
                ];
                \DB::table('agent_apply')->where('id', $id)->update($update);
                //通知用户
                if ($status == 2) {
                    //开启代理
                    $user = \Model\Admin\User::find($apply_info->user_id);
                    $agent = new \Logic\User\Agent($this->ci);
                    $profit = $agent->getProfit($apply_info->user_id);
                    if ($profit) {
                        DB::table('user_agent')->where('user_id', $apply_info->user_id)->update(['profit_loss_value' => $profit]);
                    }
                    $user->agent_switch = 1;
                    $user->setTarget($apply_info->user_id, $user->name);
                    $user->save();

                    $title = 11024;
                    $content = 11025;
                } else {
                    $title = 11026;
                    $content = 11027;
                }
                if (!empty($reply)) {
                    $content = $reply;
                }
            }else{
                $update = [
                    'remark'       => $remark,
                ];
                \DB::table('agent_apply')->where('id', $id)->update($update);
            }

            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }

        //只有在第一次同意或拒绝时候发通知，后续单独修改备注不用发
        if (empty($is_up)) {
            $user = (new \Logic\User\User($this->ci))->getInfo($apply_info->user_id);
            $exchange = 'user_message_send';
            \Utils\MQServer::send($exchange,[
                'user_id'   => $apply_info->user_id,
                'user_name' => $user['user_name'],
                'title'     => $title,
                'content'   => $content,
            ]);
        }

        return $this->lang->set(0);
    }
};