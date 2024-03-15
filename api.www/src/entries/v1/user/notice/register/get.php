<?php

use \Utils\Www\Action;

return new  class extends Action {

    const TITLE = "注册公告";
    const DESCRIPTION = "注册公告";
    const TAGS = "首页";
    const SCHEMAS = [
        [
            "id"         => "int(required) #公告id",
            "title"      => "string(required) #公告标题",
            "content"    => "string(required) #公告内容",
            "imgs"       => "string(required) #公告图片",
            "start_time" => "string(required) #开始显示时间  2012-01-01 12:12:12",
            "created"    => "string(required) #创建时间 2012-01-01 12:12:12"
        ]
    ];

    public function run() {
        $time = time();
        $data = DB::table('notice')
                 ->where('popup_type', 4)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>=', $time)
                ->orderBy('start_time','desc')
                 ->get(['id', 'title', 'content', 'imgs', DB::raw('from_unixtime(start_time) start_time') , DB::raw('from_unixtime(created) created')])
                 ->toArray();

        foreach($data as $key => &$val){
            $val->imgs = showImageUrl($val->imgs);
        }
        unset($val);

        return $data;
    }
};