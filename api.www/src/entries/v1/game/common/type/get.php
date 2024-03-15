<?php
use Utils\Www\Action;
use Model\GameMenu;

return new class() extends Action {
    const TITLE = "投注记录类型";
    const DESCRIPTION = "查询投注记录类型 Exam: /game/common/type";
    const TAGS = '游戏注单';
    const QUERY = [];
    const SCHEMAS = [
        [
            "id" => "int() #菜单id",
            "pid" => "int() #父菜单id -> 0 一级菜单",
            "type" => "string() #模式简称",
            "name" => "string() #菜单名称",
        ]
    ];

    /**
     * 入口
     * @return array
     */
    public function run() {
        $filter = GameMenu::getMenuAllList();
        foreach ($filter as $key => $val) {
            if($val['pid'] == 0){
                $filter[$key]['name'] = $this->lang->text($val['type']);
                foreach ($val['childrens'] as $key2 => $val2){
                    $filter[$key]['childrens'][$key2]['name'] = $this->lang->text($val2['type']);
                }
            }
        }
        return $filter;
    }
};

