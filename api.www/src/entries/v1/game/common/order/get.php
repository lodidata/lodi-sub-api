<?php
use Utils\Www\Action;
use Logic\GameApi\CKFormat\OrderRecordCommon;
use Model\GameMenu;

return new class() extends Action {
    const TOKEN = true;
    const TITLE = "注单记录列表";
    const DESCRIPTION = "查询投注记录列表 \r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数'] \r\n Exam: /game/common/order?type_name=KAIYUAN&category=kind_id&key=620";
    const TAGS = '游戏注单';
    const QUERY = [
        "type_name" => "string(required) #游戏类型 KAIYUAN",
        "category"  => "string(required) #类型 kind_id",
        "key"       => "int(required) #座位号 620",
        'page'      => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,20) #分页显示记录数 默认20条记录",
        'start_time'=> "date() #开始日期",
        'end_time'  => "date() #结束日期",
        'orderby'   => 'string() #排序字段 pay_money,CellScore,Profit,GameEndTime 默认GameEndTime',
        'orderbyasc' => 'string #正反序 asc,desc 默认asc'
    ];
    const SCHEMAS = [
        [
            "id" => "int(required) #id",
            "order_number" => "string(required) #订单号",
            "game_name" => "string(required) #游戏名",
            "sub_game_name" => "string(required) #子标题/根据业务不同展示给前端",
            "pay_money" => "int(required) #下注金额(真实金额 不用除100)",
            "state" => "int(required) #中奖/待开奖/未中奖",
            "mode" => "string(required) #彩票(房间/标准/快捷)/第三方",
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type = $this->request->getQueryParam('type_name');
        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 20);
        $category = $this->request->getParam('category');
        $type_name = $this->request->getParam('type_name');
        $key = $this->request->getParam('key');
        $start_time = $this->request->getParam('start_time');
        $start_time && $start_time = date('Y-m-d 00:00:00',strtotime($start_time));
        $end_time = $this->request->getParam('end_time');
        $end_time && $end_time = date('Y-m-d 23:59:59',strtotime($end_time));

        $orderby = $this->request->getParam('orderby','GameEndTime');//pay_money,CellScore,Profit
        $orderbyasc = $this->request->getParam('orderbyasc','desc');//asc,desc
        // 默认处理 type
        if (!isset($type)){
            $childList = GameMenu::getMenuAllList();
            if (count($childList) && isset($childList[0]['childrens'])){
                $type = $childList[0]['childrens'][0]['type'];
            }
        }
        $user_id = $this->auth->getUserId();
        if($this->auth->getTrialStatus()){
//             试玩 后面再补这个逻辑
            return [];
        }
        $condition = [
            'user_id'    => $user_id,
            'category'   => $category,
            'key'        => $key,
            'type_name'  => $type_name,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            'orderby' => $orderby,
            'orderbyasc' => $orderbyasc
        ];
        // 转换 筛选 为了兼容 更多第三方
        $condition = OrderRecordCommon::filter($condition);
        list($re, $total,$total_all_bet,$total_cell_score,$total_send_prize,$total_user_count) = OrderRecordCommon::records($type, $condition, $page, $pageSize);
        return $this->lang->set(
            0, [], $re, [
                'number' => $page,
                'size' => $pageSize,
                'total' => $total,
                'total_all_bet' => $total_all_bet,
                'total_cell_score' => $total_cell_score,
                'total_send_prize' => $total_send_prize,
                'total_user_count' => $total_user_count,
            ]
        );
    }


};

