<?php
namespace Logic\Pusherio;
/**
 * websocket模块
 * https://docs.mongodb.com/php-library/master/reference/method/MongoDBCollection-updateOne/
 */
class Chat extends \Logic\Logic {

    protected function publishByChat($roomId, $content) {
        $config =  $this->ci->get('settings')['pusherio'];
        $key = \Logic\Define\CacheKey::$perfix['pusherioHistory'] . $roomId;
        $len = $this->redis->llen($key);

        if ($len >= $config['history_limit']) {
            $this->redis->lpop($key);
        }

        if (is_array($content) && isset($content['timer'])) {
            $content['timer'] = date('Y-m-d H:i:s');
        }

        if (is_array($content) && isset($content['userName'])) {
            $user = $content['userName'];
            if (strlen($user) > 4) {
                $cutstr = mb_substr($user, 0, 2);
                $cutstr1 = mb_substr($user, -2);
                $user = $cutstr. "**".$cutstr1;
            }
            $content['userName'] = $user;
        }

        $this->redis->rpush($key, is_array($content) ? json_encode($content) : $content);
        return $content;
    }

    protected function getRoomId($room) {
        if (!$this->redis->exists(\Logic\Define\CacheKey::$perfix['chatLotteryRoomHash'])) {
            // echo 1;exit;
            $appId = $this->ci->get('settings')['pusherio']['app_id'];
            $rooms = \Model\Room::whereIn('room_level', [1, 2, 3])->get()->toArray();

            foreach ($rooms ?? [] as $val) {
                $hashRoom = md5($appId.'lottery_room'.$val['id']);
                $this->redis->hset(\Logic\Define\CacheKey::$perfix['chatLotteryRoomHash'], $hashRoom, $val['id']);
            }

            $this->redis->expire(\Logic\Define\CacheKey::$perfix['chatLotteryRoomHash'], 3600);
        }
        return $this->redis->hget(\Logic\Define\CacheKey::$perfix['chatLotteryRoomHash'], $room);
    }

    public function publish($type, $room, $content, $sid = '') {
        if ($type == 'lottery_room') {
            $roomId = $this->getRoomId($room);
            if ($roomId > 0) {
                $content = $this->publishByChat($roomId, $content);
            } else {
                return false;
            }
        }

        $this->redis->rpush(\Logic\Define\CacheKey::$perfix['socketioMessage'], json_encode([
            'type' => $type,
            'room' => $room,
            'sid' => $sid,
            // 'content' => is_array($content) ? json_encode($content) : $content,
            'content' => $content
        ]));
    }

    /**
     * 百家彩专用消息推送
     * @param $content
     * @param string $type
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function publishBJC($content, $type = 'bjc')
    {
        $appId = $this->ci->get('settings')['pusherio']['app_id'];
        $hashRoom = md5($appId.'lottery_bjc');
        $this->redis->rpush(\Logic\Define\CacheKey::$perfix['socketioMessage'], json_encode([
            'type' => $type,
            'room' => $hashRoom,
            'sid' => '',
            'content' => $content
        ]));
    }

    public function createUser($userId) {
        $collection = $this->mongodb->selectCollection('chat_user');
        $user = $collection->findOne(['user_id' => $userId]);
        // $user = $this->mongodb->find(['user_id' => $userId])->toArray();
        if (empty($user)) {
            // print_r($collection);
            $res = $collection->insertOne([
                'user_id' => $userId,
                'last_look_time' => time(),
                'last_send_message_time' => time(),
                'sid' => '',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
                // 'comments' => [],
            ]);

            return $res->getInsertedId(); 
        }
        return $user->_id;
    }

    public function createAdminUser($adminUserId) {
        $collection = $this->mongodb->selectCollection('chat_admin_user');
        $user = $collection->findOne(['admin_user_id' => $adminUserId]);
        // $user = $this->mongodb->find(['user_id' => $userId])->toArray();
        if (empty($user)) {
            // print_r($collection);
            $res = $collection->insertOne([
                'admin_user_id' => $adminUserId,
                'sid' => '',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
                // 'comments' => [],
            ]);

            return $res->getInsertedId(); 
        }
        return $user->_id;
    }

    /**
     * 创建用户发送给客服信息
     * @param  [type] $userId      [description]
     * @param  [type] $adminUserId [description]
     * @param  [type] $content     [description]
     * @return [type]              [description]
     */
    public function createUserToCustomer($userId, $content) {
        $id = $this->createUser($userId);
        $collection = $this->mongodb->selectCollection('chat_user');
        $collection->updateOne(['_id' => $id], [
            '$set' => [
                'last_send_message_time' => time(),
                'updated' => date('Y-m-d H:i:s'),
                'sid' => $content['sid'],
            ],
            '$push' => ['comments' => ['st' => time(), 'uid' => $userId, 'aid' => 0, 'content' => $content['content']]],
            '$inc' => ['comments_length' => 1]
        ]);
        return true;
    }

    /**
     * 创建客服发给用户信息
     * @param  [type] $userId      [description]
     * @param  [type] $adminUserId [description]
     * @param  [type] $content     [description]
     * @return [type]              [description]
     */
    public function createCustomerToUser($adminUserId, $userId, $content) {
        // 更新玩家信息
        $id = $this->createUser($userId);
        $collection = $this->mongodb->selectCollection('chat_user');
        $collection->updateOne(['_id' => $id], [
            '$set' => [
                'updated' => date('Y-m-d H:i:s'),
                // 'sid' => $content['sid'],
            ],
            '$push' => ['comments' => ['st' => time(), 'uid' => $userId, 'aid' => $adminUserId, 'content' => $content['content']]],
            '$inc' => ['comments_length' => 1]
        ]);

        // 更新后台管理员sid
        $id = $this->createAdminUser($adminUserId);
        $collection->updateOne(['_id' => $id], [
            '$set' => [
                'updated' => date('Y-m-d H:i:s'),
                'sid' => $content['sid'],
            ],
        ]);
        return true;
    }

    public function getCommentList($userId, $page = 1, $pageSize = 3) {
        $collection = $this->mongodb->selectCollection('chat_user');
        $data = $collection->findOne(
            ['user_id' => $userId], 
            ['projection' => ['comments' => ['$slice' => [1, 3]]]]
        );
        print_r($data);
        // foreach ($data as $key => $value) {
        //     print_r($value);
        // }
        exit;
    }
}