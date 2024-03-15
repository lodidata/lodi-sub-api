<?php
use Utils\Www\Action;
return new class extends Action
{
    const TITLE = "公告类型";
    const DESCRIPTION = "公告类型";
    const TAGS = "首页";
    const SCHEMAS = [
        [
            "title"  => "string(required) #公告标题",
        ]
    ];


    public function run() {
        $list = [
            [2 => $this->lang->text('LIVE')],
            [3 => $this->lang->text('SPORT')],
        ];
        return $list;
    }
};