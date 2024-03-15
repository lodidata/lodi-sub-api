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
        $reply = $params['reply'] ?? '';
        $reply = htmlspecialchars($reply);

        //检查数据是否存在
        $feedback_info = \DB::table('user_feedback')->where('id',$id)->first();
        if(empty($feedback_info)){
            return $this->lang->set(-2);
        }else{
            if($feedback_info->status != 0){
                return $this->lang->set(-2);
            }
        }

        $update = [
            'status'       => 1,
            'reply'        => $reply,
            'reply_time'   => date('Y-m-d H:i:s'),
            'operate_uid'  => $this->playLoad['uid'],
        ];
        \DB::table('user_feedback')->where('id', $id)->update($update);

        //通知用户
        $title = 11031;
        $content = 11032;
        $exchange = 'user_message_send';
        \Utils\MQServer::send($exchange,[
            'user_id'   => $feedback_info->user_id,
            'user_name' => $feedback_info->user_name,
            'title'     => $title,
            'content'   => $content,
        ]);
        $lock_code_key  = "user_feedback_pending".$feedback_info->user_id;
        $this->redis->del($lock_code_key);

        return $this->lang->set(0);
    }
};
