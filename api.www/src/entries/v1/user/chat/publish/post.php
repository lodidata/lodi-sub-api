<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "聊天消息推送";
    const DESCRIPTION = "";
    const TAGS = "会员消息";
    const PARAMS = [
        'type' => "string(required) #消息类型-根据聊天类型配置 如：customer_service客服信息",
        'room' => "string(required) #房间名称",
        'content' => [
            'userName' => "string() #用户名称",
            'nickName' => "string() #用户昵称 名称和昵称必须填写一个",
        ],
        'sid'  => "string() #"
    ];



    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $type = $this->request->getParam('type');
        $room = $this->request->getParam('room');
        $content = $this->request->getParam('content');
        $sid = $this->request->getParam('sid');

        if(isset($content['userName'])) {
            $content['userName'] = substr($content['userName'],0,2) . '***' . substr($content['userName'],-2);
        }
        if(isset($content['nickName']) && isset($content['userName']) && $content['userName'] == $content['nickName']) {
            $content['nickName'] = substr($content['nickName'],0,2) . '***' . substr($content['nickName'],-2);
        }

        if (empty($room) || empty($content) || empty($type)) {
            return $this->lang->set(10, [], [], compact('type', 'room', 'content', 'sid'));
        }
        // $this->redis->rpush(\Logic\Define\CacheKey::$perfix['socketioMessage'], json_encode(compact('room', 'content', 'type', 'sid')));

        $chat = new \Logic\Pusherio\Chat($this->ci);
        $userId = $this->auth->getUserId();
        if ($type == 'customer_service') {
            // $chat->createUser($userId);
            // if (isset($content['type']) && $content['type'] == 'md') {
            $chat->createUserToCustomer($userId, compact('room', 'content', 'type', 'sid'));
            // }
            // $chat->getCommentList($userId);
        }

        if (isset($content['messageType']) && $content['messageType'] == 2) {
            $chat->publish($type, $room, $content, $sid);
        }
        return $this->lang->set(0);
    }
};