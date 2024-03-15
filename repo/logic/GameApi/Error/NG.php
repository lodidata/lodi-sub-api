<?php

namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class NG
 * @package Logic\GameApi\Error
 */
class NG extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'NG')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_mg')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'GAME',
                        'order_number' => $val['roundId'],
                        'game_type' => 'NG',
                        'type_name' => 'NG',
                        'game_id' => 137,
                        'server_id' => 0,
                        'account' => $val['playerNativeId'],
                        'bet' => $val['amount'],
                        'profit' => $val['earn'],
                        'date' => $val['created'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_ng')->where('roundId', $value['roundId'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}