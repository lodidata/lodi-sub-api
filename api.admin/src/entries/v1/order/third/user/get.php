<?php
/**
 * User: nk
 * Date: 2019-01-07
 * Time: 10:57
 * Des : 查看订单类型 <彩票也算本平台第三方>
 * 注意 : 这里没有彩票逻辑,只有第三方,与 app 接口有本质差别
 */

use Logic\Admin\BaseController;
use Logic\GameApi\Format\OrderRecordCommon;
use Logic\GameApi\Game\KAIYUAN as kyqp;

return new class() extends BaseController {
    const TITLE = "GET 用户投注统计";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "";

    const QUERY = [];
    const SCHEMAS = [
        []
    ];


    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        global $app;
        $ci = $app->getContainer();
        $userLogic = new \Logic\User\User($app->getContainer());

        $type = $this->request->getQueryParam('type_name', 'KAIYUAN');
        $user_name = $this->request->getParam('user_name');
        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 20);
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');

        !isset($type) && $type = "KAIYUAN";
        $query = DB::connection('slave')->table('orders');

        $query->where('game_type', $type);

        //用户名过滤
        if (isset($user_name)) {
            $user_id = \DB::connection('slave')->table('user')->where('name', $user_name)->value('id');
            $query->where('user_id', $user_id);
        }

        // 订单时间过滤
        if ($start_time) {
            $query->where('order_time', '>=', $start_time);
        } else {
            $query->where('order_time', '>=', date('Y-m-d'));
        }

        if ($end_time) {
            $query->where('order_time', '<=', $end_time);
        } else {
            $query->where('order_time', '<=', date('Y-m-d H:i:s'));
        }
        $statisQuery = clone $query;
        $countQuery = clone $query;
        $data = $query->distinct()->groupBy('user_id')
            ->forPage($page, $pageSize)
            ->get([
                'user_id',
                \DB::raw('COUNT(id) AS `count`'),
                \DB::raw('SUM(bet) AS `bet`'),
                \DB::raw('SUM(bet) AS `valid_bet`'),
                \DB::raw('SUM(send_money) AS `send_money`'),
                \DB::raw('"-" AS `fee`'),
                \DB::raw('MAX(order_time) AS `order_time`'),
            ])
            ->toArray();
        foreach ($data as $key => $value) {
            $value->user_name = $userLogic->getGameUserNameById($value->user_id);
        }

        // 统计数据 所有统计
        $statisTotal = (array)$statisQuery->first([
            \DB::raw('SUM(bet) AS sum_bet'),
            \DB::raw('SUM(bet) AS sum_valid_bet'),
            \DB::raw('SUM(send_money) AS sum_send_money')
        ]);
        $statisCount = (array)$countQuery->distinct()->count('user_id');
        $statisTotal['count'] = $statisCount[0];
        $statisTotal['sum_fee'] = '-';
        $statisTotal['sum_bet'] = $statisTotal['sum_bet'] ?? 0;
        $statisTotal['sum_valid_bet'] = $statisTotal['sum_bet'] ?? 0;
        $statisTotal['sum_send_money'] = $statisTotal['sum_bet'] ?? 0;

        // 统计数据 当前页面
        $statisCurrent['count'] = count($data);
        $statisCurrent['sum_bet'] = array_sum(array_column($data, 'bet'));
        $statisCurrent['sum_valid_bet'] = array_sum(array_column($data, 'bet'));;
        $statisCurrent['sum_send_money'] = array_sum(array_column($data, 'send_money'));
        $statisCurrent['sum_fee'] = '-';

        return $this->lang->set(
            0, [], $data, [
                'number' => $page,
                'size' => $pageSize,
                'total' => $statisTotal['count'],
                'total_statis' => $statisTotal,
                'current_statis' => $statisCurrent
            ]
        );
    }


};