<?php
use Utils\Www\Action;
use Logic\GameApi\CKFormat\OrderRecordCommon;
use Model\GameMenu;

return new class() extends Action {
    const TOKEN = true;
    const TITLE = "游戏过滤条件";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "查询投注记录 Exam: /game/common/order";
    const TAGS = '游戏注单';
    const QUERY = [
        "type_name" => "string() #游戏类型 如：JOKER"
    ];
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
        $type = $this->request->getQueryParam('type_name');
        // 默认处理 type
        if (!isset($type)){
            $childList = GameMenu::getMenuAllList();
            if (count($childList) && isset($childList[0]['childrens'])){
                $type = $childList[0]['childrens'][0]['type'];
            }
        }
        $res = OrderRecordCommon::filterDetail($type,true);
        return $res;
    }



};