<?php
use Logic\Admin\BaseController;
use Logic\GameApi\CKFormat\OrderRecordCommon;

return new class() extends BaseController {
    const TITLE = "GET 投注记录";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "查询投注记录";

    const QUERY = [];
    const SCHEMAS = [
        [
            'order_number' => '订单号',
            'user_name' => '用户名',
            'GameStartTime' => '游戏开始时间',
            'GameEndTime' => '游戏结束时间',
            'game_name' => '游戏 > 游戏房间名',
            'TableID' => '桌号 > 椅子号',
            'pay_money' => '总下注金额',
            'CellScore' => '有效下注',
            'Profit' => '盈亏',
            'Revenue' => '抽水',
        ]
    ];


    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $type = $this->request->getQueryParam('type_name', 'KAIYUAN');
        $user_name = $this->request->getParam('user_name');
        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 20);
        $order_number = $this->request->getParam('order_number');
        $category = $this->request->getParam('category');
        $key = $this->request->getParam('key');
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');
        $orderby = $this->request->getParam('orderby','GameEndTime');//pay_money,CellScore,Profit
        $orderbyasc = $this->request->getParam('orderbyasc','desc');//asc,desc

        // 异常处理
        !isset($type) && $type = "KAIYUAN";
        $condition = [
            'user_name' => $user_name,
            'order_number' => $order_number,
            'key' => $key,
            'category' => $category,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'info' => true,
            'admin_info' => true,
            'orderby' => $orderby,
            'orderbyasc' => $orderbyasc
        ];
        // 转换 筛选 为了兼容 更多第三方
        $condition = OrderRecordCommon::filter($condition);
        list($re, $total,$total_all_bet,$total_cell_score,$total_send_prize,$total_user_count) = OrderRecordCommon::recordsAdmin($type, $condition, $page, $pageSize);
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

