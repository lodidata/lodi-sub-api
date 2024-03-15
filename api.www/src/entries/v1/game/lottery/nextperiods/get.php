<?php
use Utils\Www\Action;
use Model\LotteryInfo;
return new class extends Action
{
    const TITLE = "GET 追号 获取期号";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "追号 获取期号";
    const TAGS = "彩票";
    const QUERY = [
       "id" => "int(required) #彩票id",
       "size" => "int(required) #返回多少期"
   ];
    const SCHEMAS = [
       [
           "lottery_number" => "string(required) #彩票期号",
           "end_time" => "int(required)    #彩票结束时间"
       ]
   ];

    public function run() {
        $id = $this->request->getQueryParam('id', 0);
        $size = $this->request->getQueryParam('size', 10);
        if ($id == 0 || $size > 100) {
            return $this->lang->set(13);
        }
        
        return LotteryInfo::getCacheNextPeriods($id, $size);
    }
};