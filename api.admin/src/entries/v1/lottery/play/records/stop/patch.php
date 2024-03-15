<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\LotteryOrder;

return new class() extends BaseController {
    const TITLE = "更改彩票注单详情";
    const HINT = "url的?\\d替换成注单ID值";
    const DESCRIPTION = "更改注单详情(目前只支持更改状态)";

    const PARAMS = [
       "state" => "enum[canceled]() #状态"
   ];
    const SCHEMAS = [
   ];


    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id) {
        /*======================操作日志代码=========================*/
        $order=DB::table('lottery_order')
            ->where('id', $id)->get()->first();
        $order=(array)$order;
        /*===============================================*/
        $res=(new \Logic\Lottery\Order($this->ci))->cancel($id);

        /*======================操作日志代码=========================*/
        (new Log($this->ci))->create($order['user_id'], $order['user_name'], Log::MODULE_ORDER, '注单查询', '注单查询', '撤单', 1,"注单号:{$order['order_number']}/手动撤单" );
        /*===============================================*/
        return $res;
    }
};
