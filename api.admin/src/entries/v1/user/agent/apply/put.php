<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Logic\Set\SystemConfig;
use Model\GameMenu;

return new class() extends BaseController
{
    const TITLE = '审核代理';
    const DESCRIPTION = '审核代理';

    const QUERY = [
    ];

    const SCHEMAS = [
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id)
    {
        $this->checkID($id);

        $params = $this->request->getParams();
        $status = $params['status'] ?? 0;
        $reply = $params['reply'] ?? '';
        $reply = trim($reply);

        if(!in_array($status, [1,2])){
            return $this->lang->set(-2);
        }

        //检查数据是否存在
        try {
            $this->db->getConnection()->beginTransaction();
            $apply_info = \DB::table('agent_apply')->where('status', 0)->lockForUpdate()->find($id);
            if (empty($apply_info)) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(-2);
            }

            $update = [
                'status' => $status,
                'reply' => $reply,
                'operate_time' => date('Y-m-d H:i:s'),
                'operate_uid' => $this->playLoad['uid'],
                'deal_user' => 1,
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
            $this->db->getConnection()->commit();
        }catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }

        $user = (new \Logic\User\User($this->ci))->getInfo($apply_info->user_id);
        $exchange = 'user_message_send';
        \Utils\MQServer::send($exchange,[
            'user_id'   => $apply_info->user_id,
            'user_name' => $user['user_name'],
            'title'     => $title,
            'content'   => $content,
        ]);

        return $this->lang->set(0);
    }
};
