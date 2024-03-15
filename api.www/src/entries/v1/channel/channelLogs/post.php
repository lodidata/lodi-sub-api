<?php

use Model\UserChannelLogs;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "记录推广点击";
    const DESCRIPTION = "记录推广点击";
    const TAGS = "";
    const QUERY = [

    ];
    const SCHEMAS = [
        "channel_id" => "int() #渠道id"
    ];
    //登录来源对应
    protected $platformIds = [
        'pc' => 1,
        'h5' => 2,
        'android' => 4,
        'ios' => 3,
    ];

    public function run()
    {

        $user_id = $this->auth->getUserId();
        $channel_id = \Utils\Utils::matchChinese($this->request->getParam('channel_id'));
        if (!$channel_id) {
            return $this->lang->set(0);
        }
        $user_ip = \Utils\Client::getIp();
        $redis_key = 'channel_logs:' . $channel_id . '-' . $user_ip;
        if ($this->redis->get($redis_key))
            return $this->lang->set(0);
        $count = db::table('channel_management')->where('number', $channel_id)->count();
        if (!$count)
            return $this->lang->set(0);
        // 写入点击日志
        UserChannelLogs::create(
            [
                'user_id' => $user_id,
                'channel_id' => $channel_id,
                'platform' => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
            ]
        );

        // 点击同时计算独立访问次数与总访问次数写入缓存
        $key_distinct = "channel_distinct:{$channel_id}";
        $key_total = "channel_total:{$channel_id}";
        $this->redis->pfadd($key_distinct, $user_ip);
        $this->redis->incr($key_total);

        $this->redis->setex($redis_key, 10, $channel_id);
        return $this->lang->set(0);
    }
};