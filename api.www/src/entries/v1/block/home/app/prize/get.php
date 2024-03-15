<?php
use Utils\Www\Action;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = "获取APP首页顶部轮播和中奖人数";
    const TAGS = '首页';
    const SCHEMAS = [
       [
           "content" => "string() #轮播文字",
           "reward_man_total" => "string() #中奖人数",
           "reward_money_total" => "string() #中奖金额"
       ]
   ];


    public function run() {
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['appHomePage']);
        $data = json_decode($data, true);
        if($data) {
            foreach ($data['content'] as &$key) {
                $key['money'] = $key['money'] > 200000000 ? random_int(100000, 200000000) : $key['money'];
            }
        }
        return $data;
    }
};