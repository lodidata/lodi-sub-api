<?php

namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class Hb
 * @package Logic\GameApi\Error
 */
class HB extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            11 => ['id' => 128, 'game' => 'GAME', 'type' => 'HB'],
            8 => ['id' => 129, 'game' => 'TABLE', 'type' => 'HBTAB'],
            6 => ['id' => 130, 'game' => 'QP', 'type' => 'HBQP'],
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'HB')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            $wagersId = $val['GameTypeId'];
            try {
                $is_insert = \DB::table('game_order_hb')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$wagersId]['game'],
                        'order_number' => $val['GameInstanceId'],
                        'game_type' => $platformTypes[$wagersId]['type'],
                        'type_name' => $platformTypes[$wagersId]['type'],
                        'game_id' => $platformTypes[$wagersId]['id'],
                        'server_id' => 0,
                        'account' => $val['Username'],
                        'bet' => $val['Stake'],
                        'profit' => $val['Payout'],
                        'date' => $val['DtCompleted'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_hb')->where('GameInstanceId', $value['GameInstanceId'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}