<?php
use Utils\Www\Action;
use Logic\GameApi\CKFormat\OrderRecordCommon;

return new class() extends Action {
    const TITLE = "GET 过滤条件";
    const DESCRIPTION = "查询投注记录";

    const QUERY = [];
    const SCHEMAS = [
        [
            'title' => '筛选标题',
            'category' => '筛选id',
            'list' => [
                'key' => '筛选子id',
                'name' => '筛选名(只是显示)'
            ]
        ]
    ];

    public function run() {
        $type = $this->request->getQueryParam('type_name', 'KAIYUAN');
        !isset($type) && $type = "KAIYUAN";
        return OrderRecordCommon::filterDetail($type);
    }

};