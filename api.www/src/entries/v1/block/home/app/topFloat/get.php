<?php

use Utils\Www\Action;
use Model\Spread;
use Logic\Define\CacheKey;

return new class extends Action {
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


        $data = \Model\TopConfig::where('download','1')
                      ->orderby('id', 'asc')
                      ->get()
                      ->toArray();

        $result = array_map(function ($row) {
            return [
                'logo_img'       => showImageUrl($row['logo_img']),
                'description'    => $row['description'],
                'title'          => $row['title'],
                'jump_type'      => $row['jump_type'],
                'url'            => $row['url'],
                'commit'         => $row['commit'],
            ];
        }, $data);



        return $result;
    }
};