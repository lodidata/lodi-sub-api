<?php

namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class WMATCH
 * @package Logic\GameApi\Error
 */
class WMATCH extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            140 => ['game' => 'GAME', 'type' => 'WMATCH'],
            141 => ['game' => 'BY', 'type' => 'WMATCHBY'],
            142 => ['game' => 'QP', 'type' => 'WMATCHQP'],
            143 => ['game' => 'TABLE', 'type' => 'WMATCHTAB'],
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'MG')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            $wagersId = $val['gameTypeId'];
            try {
                $is_insert = \DB::table('game_order_wmatch')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$wagersId]['game'],
                        'order_number' => $val['roundId'],
                        'game_type' => $platformTypes[$wagersId]['type'],
                        'type_name' => $platformTypes[$wagersId]['type'],
                        'game_id' => $wagersId,
                        'server_id' => 0,
                        'account' => $val['externalUserId'],
                        'bet' => $val['totalBetAmount'],
                        'profit' => $val['totalWinAmount'],
                        'date' => $val['roundEndTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_wmatch')->where('roundId', $value['roundId'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}