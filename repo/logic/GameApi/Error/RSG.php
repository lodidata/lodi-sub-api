<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class JILI
 * @package Logic\GameApi\Error
 */
class JILI extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            150 => ['game' => 'GAME', 'type' => 'RSG'],
            151 => ['game' => 'BY', 'type' => 'RSGBY'],
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'RSG')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array) $value;
            $val = json_decode($value['json'], true);
            $wagersId = $val['game_menu_id'];
            try {
                $is_insert = \DB::table('game_order_jili')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$wagersId]['game'],
                        'order_number' => $val['SequenNumber'],
                        'game_type' => $platformTypes[$wagersId]['type'],
                        'type_name' => $platformTypes[$wagersId]['type'],
                        'game_id' => $wagersId,
                        'server_id' => 0,
                        'account' => $val['UserId'],
                        'bet' => $val['BetAmt'],
                        'profit' => $val['WinAmt'],
                        'date' => $val['PlayTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_jili')->where('SequenNumber', $value['order_number'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}