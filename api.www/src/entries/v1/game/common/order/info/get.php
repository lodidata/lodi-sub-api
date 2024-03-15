<?php

use Utils\Www\Action;
use Logic\GameApi\CKFormat\OrderRecordCommon;
use Model\GameMenu;

return new class() extends Action {
    const TOKEN = true;
    const TITLE = "注单记录详情";
    const DESCRIPTION = "查询投注记录详情 Exam: /game/common/order/info";
    const TAGS = '游戏注单';
    const QUERY = [
        'type_name' => "string(required) #游戏分类 JOKER",
        'order_number' => "string(required) #订单号 12324234234"
    ];
    const SCHEMAS = [
            'logo' => 'string(required) #游戏图标',
            'game_name' => 'string(required) #游戏名称',
            'sub_game_name' => 'string(required) #子标题',
            'pay_money' => 'string(required) #下注金额(真实金额)',
            'send_money' => 'string(required) #派彩金额',
            'detail' => [  // 游戏投注详情
                'key' => 'string(required) #前端显示名',
                'value' => 'string(required) #前端显示值',
            ],
            'chase_list' => [ // 追号列表(彩票特殊)
            ]
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type = $this->request->getQueryParam('type_name');
        $order_number = $this->request->getParam('order_number');
        // 默认处理 type
        if (!isset($type)){
            $childList = GameMenu::getMenuAllList();
            if (count($childList) && isset($childList[0]['childrens'])){
                $type = $childList[0]['childrens'][0]['type'];
            }
        }
        $user_id = $this->auth->getUserId();
        if($this->auth->getTrialStatus()){
            // 试玩 后面再补这个逻辑
            return [];
        }
        $condition = [
            'user_id'      => $user_id,
            'order_number' => $order_number,
            // 需要获取开奖号码
            'info' => true,
        ];
        // 转换 筛选 为了兼容 更多第三方
        $condition = array_filter($condition);
        $re = OrderRecordCommon::detail($type, $condition);
        return $re;
    }

};


