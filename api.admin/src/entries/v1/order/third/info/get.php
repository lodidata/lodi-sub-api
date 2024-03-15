<?php
use Logic\GameApi\CKFormat\OrderRecordCommon;


return new class() extends \Logic\Admin\BaseController {
    const TITLE = "GET 投注记录详情";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "查询投注记录详情";

    const QUERY = [];
    const SCHEMAS = [
        [
            'logo' => '游戏图标',
            'game_name' => '游戏名称',
            'sub_game_name' => '子标题',
            'pay_money' => '下注金额(真实金额)',
            'send_money' => '派彩金额',
            'detail' => [  // 游戏投注详情
                'key' => '前端显示名',
                'value' => '前端显示值',
            ],
            'chase_list' => [ // 追号列表(彩票特殊)

            ]
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $type = $this->request->getQueryParam('type_name', 'KAIYUAN');
        $order_number = $this->request->getParam('order_number');
        // 异常处理
        !isset($type) && $type = "KAIYUAN";
        $condition = [
            'order_number' => $order_number,
            'info' => true,
        ];
        // 转换 筛选 为了兼容 更多第三方
        $condition = array_filter($condition);
        $re = OrderRecordCommon::detail($type, $condition);
        return $re;
    }

};
