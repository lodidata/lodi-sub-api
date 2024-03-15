<?php
use Utils\Www\Action;
use Model\Lottery;
return new class extends Action
{
    const TITLE = "GET 彩票列表";
    const TAGS = "彩票";
    const QUERY = [
       "bet_type" => "enum(fase,standard) #(fast=快速 ,standard=标准)不传值就是全部"
   ];
    const SCHEMAS = [
           [
               "id" => "int() #id",
               "pid" => "int() #父级id(pid为0就是一级)",
               "name" => "string() #彩票名称",
               "open_type" => "int() #打开方式(0:本地打开, 1:第三方打开)",
               "pc_open_type" => "int() #pc打开方式(1:弹窗，2:新窗口，3:本页面跳转)",
               "state" => "enum[standard,fast,auto,enabled]() #状态(standard:启用标准, fast:启用快速, auto:自动派奖, enabled:有效)"
           ]
   ];


    public function run() {
        $redis_key = \Logic\Define\CacheKey::$perfix['simpleChildList'];
        $data = $this->redis->get($redis_key);
        if (empty($data)) {
            $data = Lottery::getAllChildLottery();
            if($data){
                $this->redis->set($redis_key, json_encode($data));
                $this->redis->expire($redis_key, 60*5);
            }
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    }
};