<?php

namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class MG
 * @package Logic\GameApi\Error
 */
class MG extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'Slot' => ['id' => 118, 'game' => 'GAME', 'type' => 'MG'],
            'qp' => ['id' => 119, 'game' => 'QP', 'type' => 'MGQP'],
            'Casino' => ['id' => 122, 'game' => 'LIVE', 'type' => 'MGLIVE'],
            'Fishing' => ['id' => 123, 'game' => 'BY', 'type' => 'MGBY'],
            'Arcade' => ['id' => 121, 'game' => 'ARCADE', 'type' => 'MGJJ'],
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'MG')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            $wagersId = $val['GameCategoryType'];
            if ($wagersId != 5) {
                $wagersId = 1;
            }
            try {
                $is_insert = \DB::table('game_order_mg')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$wagersId]['game'],
                        'order_number' => $val['OCode'],
                        'game_type' => $platformTypes[$wagersId]['type'],
                        'type_name' => $platformTypes[$wagersId]['type'],
                        'game_id' => $platformTypes[$wagersId]['id'],
                        'server_id' => 0,
                        'account' => $val['playId'],
                        'bet' => $val['betAmount'],
                        'profit' => $val['payoutAmount'],
                        'date' => $val['createdTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_mg')->where('betUID', $value['betUID'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}