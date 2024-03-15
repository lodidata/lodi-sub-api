<?php

use Utils\Www\Action;
use Model\Spread;
use Logic\Define\CacheKey;

return new class extends Action {
    const TAGS = '首页';
    const TITLE = '获取推广引图片';
    const QUERY = [
    ];

    const SCHEMAS = [
        [
            "name"    => '标题',
            "sort"    => '排序',
            "picture" => '图片地址',
        ]
    ];


    public function run() {
        $cacheKey = CacheKey::$perfix['spreadPicList'];

        $data = $this->redis->get($cacheKey);

        if ($data) {
            return json_decode($data, true);
        }

        $data = Spread::where('status', 'enabled')
                      ->orderby('sort', 'asc')
                      ->orderby('created', 'asc')
                      ->get()
                      ->toArray();

        $result = array_map(function ($row) {
            return [
                'name'    => $row['name'],
                'sort'    => $row['sort'],
                'picture' => $row['picture'],
            ];
        }, $data);

        if ($result) {
            $this->redis->setex($cacheKey, 3600, json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }
};