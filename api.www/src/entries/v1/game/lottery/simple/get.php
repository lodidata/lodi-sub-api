<?php
use Utils\Www\Action;
use Model\Lottery;
return new class extends Action
{
    const TITLE = "GET 彩票列表";
    const HINT = "开发的技术提示信息";
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
        $betType    = $this->request->getQueryParam('bet_type');
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['simpleList'].$betType);
        if (empty($data)) {
            $alllottery = Lottery::getAllLottery();
            if (!empty($alllottery)) {
                $data = [];
                foreach($alllottery as $v)
                {
                    $a                 = explode(',', $v['state']);
                    $v['id']           = (int)$v['id'];
                    $v['pid']          = (int)$v['pid'];
                    $v['open_type']    = (int)$v['open_type'];
                    $v['pc_open_type'] = (int)$v['pc_open_type'];

                    if($v['id']=='57'){
                        continue;
                    }
                    if($betType == 'fast' && intval($v['pid']) != 0 && in_array('fast', $a))
                    {
                        array_push($data, $v);
                    }
                    elseif($betType == 'standard' && intval($v['pid']) != 0 && in_array('standard', $a))
                    {
                        array_push($data, $v);
                    }
                    elseif(empty($betType))
                    {
                        array_push($data, $v);
                    }
                }
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['simpleList'].$betType, 30, json_encode($data));
            }
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    }
};