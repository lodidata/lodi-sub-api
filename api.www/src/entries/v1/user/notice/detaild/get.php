<?php
use Utils\Www\Action;
use Model\Notice;
return new class extends Action
{
    const TITLE = "公告列表详情";
    const DESCRIPTION = "公告列表详情";
    const TAGS = "首页";
    const SCHEMAS = [
       [
            "id" => "int(required) #公告id",
           "title"  => "string(required) #公告标题",
           "content" => "string(required) #公告内容",
           "start_time" => "string(required) #开始显示时间  2012-01-01 12:12:12",
           "created"    => "string(required) #创建时间 2012-01-01 12:12:12"
       ]
   ];


    public function run() {
        $id = (int) $this->request->getQueryParam('id');
        $time = time();
        $data = Notice::where('status', 1)
                        ->whereIn('send_type', [1, 3])
                        ->where('popup_type', 3)
                       // ->where('language_id', $this->language_id)
                        ->where('start_time', '<=', $time)
                        ->where('end_time', '>=', $time)
                        ->where('id',$id)
                        ->orderBy('start_time','desc')
                        ->get(['id', 'title', 'content', 'imgs', DB::raw('from_unixtime(start_time) start_time') , DB::raw('from_unixtime(created) created')])
                        ->first();
        if(!empty($data)){
            $data->imgs=showImageUrl($data->imgs);
        }


        return $this->lang->set(0, [], $data);
    }
};