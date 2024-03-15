<?php
use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取推广视频";
    const DESCRIPTION = "获取推广视频";
    const TAGS = "";
    const QUERY = [

    ];
    const SCHEMAS = [
        'title'       => "string(required) #标题",
        'link'     => "string(required) #视频连接",
        'location'    => "string(required) #位置",
        "status"=> "int(required) #启用状态"
    ];

    public function run()
    {
        $info = (array)DB::table('agent_video_conf')->where('status',1)->orderBy('id','desc')->first();
        return $this->lang->set(0, [], $info, []);
    }
};