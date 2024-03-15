<?php

use Logic\Admin\BaseController;

return new class extends BaseController {
    const TAGS = '首页';
    const TITLE = '顶部飘浮下载按钮';
    const QUERY = [
    ];

    const SCHEMAS = [
        [
            'description' => 'string #文字描述',
            'title '      => 'string #按钮文本',
            'url'         => 'string #跳转链接',
            'download'    => 'int #下载开关 1 开 0 关',
        ]
    ];


    public function run() {
        $data = DB::table('top_config')->first();
        $data->logo_img = showImageUrl($data->logo_img);
        return $this->lang->set(0,[],$data);
    }
};