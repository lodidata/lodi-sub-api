<?php

use Utils\Www\Action;
use Model\GameMenu;
use Logic\GameApi\Format\OrderRecordCommon;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "团队投注记录筛选";
    const DESCRIPTION = "团队投注记录筛选";
    const TAGS = "代理返佣";
    const QUERY = [];
    const SCHEMAS = [
        [
            'id'    => "int(required) #游戏分类ID",
            'pid'   => "int(required) #上级游戏分类ID",
            'type'  => "string(required) #游戏类型 如：KAIYUAN",
            'name'  => "string(required) #游戏类型名称 如：开元棋牌",
        ],
    ];

    public function run() {
        $menu_childs = GameMenu::getMenuChilds();
        // 加入过滤条件
        foreach ($menu_childs as $pos => $menu){
            $type =  $menu['type'];
            $menu_childs[$pos]['category'] = 'state';
            if (isset(OrderRecordCommon::TYPE_TARGET_STATE[$type])){
                $menu_childs[$pos]['filter'] = OrderRecordCommon::TYPE_TARGET_STATE[$type];
            }else{
                $menu_childs[$pos]['filter'] = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            }
        }
        return $menu_childs;
    }

};