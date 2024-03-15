<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class AT
 * @package Logic\GameApi\Error
 */
class AT extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'slot' => ['id' => 88, 'game' => 'GAME', 'type' => 'AT'],
            'fish' => ['id' => 89, 'game' => 'BY', 'type' => 'ATBY'],
            'arcade' => ['id' => 90, 'game' => 'ARCADE', 'type' => 'ATJJ'],
        ];
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'AT')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_at')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$val['gameType']]['game'],
                        'order_number' => $val['order_number'],
                        'game_type' => $platformTypes[$val['gameType']]['type'],
                        'type_name' => $platformTypes[$val['gameType']]['type'],
                        'game_id' => $platformTypes[$val['gameType']]['id'],
                        'server_id' => 0,
                        'account' => $val['player'],
                        'bet' => $val['bet'],
                        'profit' => $val['win'] - $val['bet'],
                        'date' => $val['createdAt'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_at')->where('order_number', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}