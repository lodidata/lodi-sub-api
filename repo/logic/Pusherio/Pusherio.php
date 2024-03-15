<?php

namespace Logic\Pusherio;
/**
 * websocket模块
 */
class Pusherio extends \Logic\Logic {

    protected $config;

    public function init($configName = 'pusherio') {
        $this->config = $this->ci->get('settings')[$configName];
        return $this;
    }

     /**
     * 查询房间信息
     * @param  [type] $lotteryId [description]
     * @param  [type] $roomId    [description]
     * @return [type]            [description]
     */
    public function getInfo($lotteryId, $roomId) {
        $config = $this->config;
        $room = $config['channel_prefix'] . "{$lotteryId}-{$roomId}";
        return [
            'room' => $room,
            'app_server_url' => $this->_getServerUrl($room, $config['server_url']),
        ];
    }

    /**
     * 查询历史
     * @param  [type] $room [description]
     * @return [type]       [description]
     */
    public function getHistory($room) {
        $config = $this->config;
        $key = \Logic\Define\CacheKey::$perfix['pusherioHistory'] . $room;
        $data = $this->redis->lrange($key, 0, -1);
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                // $data[$key] = isset($value[0]) && $value[0] == '{' ? json_decode($value, true) : $value;
                $data[$key] = isset($value[0]) && $value[0] == '{' ? json_decode($value, true) : '';
            }
        }
        return $data;
    }

    /**
     * 广播消息
     * @param  [type] $users    [description]
     * @param  [type] $room    [description]
     * @param  [type] $content [description]
     * @return [type]          [description]
     */
    public function publish($users, $room, $content) {
        $config = $this->config;
        $key = \Logic\Define\CacheKey::$perfix['pusherioHistory'] . $room;
        $len = $this->redis->llen($key);

        if ($len >= $config['history_limit']) {
            $this->redis->lpop($key);
        }

        if (is_array($content) && isset($content['timer'])) {
            $content['timer'] = date('Y-m-d H:i:s');
        }

        if (is_array($content) && isset($content['nickName']) && !empty($content['nickName'])) {
            $nick_name = $content['nickName'];
            if (strlen($nick_name) > 4) {
                $cutstr = mb_substr($nick_name, 2, -2);
                $nick_name = str_replace($cutstr, '**', $nick_name);
            }
            $content['nickName'] = $nick_name;
        }

        if (is_array($content) && isset($content['userName'])) {
            $user = $content['userName'];
            if (strlen($user) > 4) {
                $cutstr = mb_substr($user, 2, -2);
                $user = str_replace($cutstr, '**', $user);
            }
            $content['userName'] = $user;
        }

        $this->redis->rpush($key, is_array($content) ? json_encode($content) : $content);
        $this->redis->expire($key, $config['history_expire']);

        return $this->_post($config, $room, $content);
    }

    /**
     * 提交 数据
     * @param  [type] $config  [description]
     * @param  [type] $room    [description]
     * @param  [type] $content [description]
     * @return [type]          [description]
     */
    protected function _post($config, $room, $content) {
        $data = [
            'app_id' => $config['app_id'],
            'timest' => time(),
            'content' => $content,
            'room' => $room,
        ];

        $data['sign'] = md5(md5($data['app_id'] . $data['timest']) . $config['app_secret']);
        $dataString = json_encode($data);
        $serverUrl = isset($config['server_internal_url']) ? $config['server_internal_url'] : $config['server_url'];
        $serverUrl = $this->_getServerUrl($room, $serverUrl);
        $ch = curl_init($serverUrl . '/publish');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataString))
        );

        $result = curl_exec($ch);
        return json_decode($result, true);
    }

    /**
     * 查询服务器所在
     * @param  [type] $room      [description]
     * @param  [type] $serverUrl [description]
     * @return [type]            [description]
     */
    protected function _getServerUrl($room, $serverUrl) {
        if (is_string($serverUrl)) {
            return $serverUrl;
        }

        if (is_array($serverUrl)) {
            $num = 0;
            $index = 0;
            $model = count($serverUrl);
            while (true) {
                if (!isset($room[$index])) {
                    break;
                }
                $num += ord($room[$index]);
                $index++;
            }
            return $serverUrl[$num % $model];
        }
    }
}