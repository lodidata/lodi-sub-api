<?php

use Utils\Www\Action;
use Logic\Pusherio\Pusherio;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "获取 pusher io历史信息";
    const DESCRIPTION = "pusher io 配置信息 http://www.api.sayahao.com/pusher/io/history?room=";
    const TAGS = "彩票";
    const QUERY = [
        "room" => "string(option) # 房间标识从info接口拉取"
    ];
    const SCHEMAS = [
        200 => [
            "content" => "string(option) # 消息内容(格式自定义)"
        ]
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $room = $this->request->getQueryParam('room');

        if (empty($room)) {
            return $this->lang->set(10);
        }

        $this->setRoomNum($room);

        return (new Pusherio($this->ci))->init()->getHistory($room);
    }


    public function setRoomNum($room)
    {
        $userId = $this->auth->getUserId();
        $roomData = (array)DB::table('room')->where('id', '=', $room)
            ->get()
            ->first();
        $res=['user_id'.$userId=>$roomData['number']];
        $this->redis->hMset(\Logic\Define\CacheKey::$perfix['hallNum'] . '_' . $room, $res);

    }
};