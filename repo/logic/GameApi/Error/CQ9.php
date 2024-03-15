<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class CQ9
 * @package Logic\GameApi\Error
 */
class CQ9 extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'slot' => ['id' => 74, 'game' => 'GAME', 'type' => 'CQ9', 'table' => 'game_order_cqnine_dz'],
            'fish' => ['id' => 75, 'game' => 'BY', 'type' => 'CQ9BY', 'table' => 'game_order_cqnine_by'],
            'arcade' => ['id' => 85, 'game' => 'ARCADE', 'type' => 'CQ9JJ', 'table' => 'game_order_cqnine_jj'],
            'table' => ['id' => 86, 'game' => 'TABLE', 'type' => 'CQ9TAB', 'table' => 'game_order_cqnine_table'],
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'CQ9')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table($platformTypes[$val['gametype']]['table'])->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$val['gametype']]['game'],
                        'order_number' => $val['round'],
                        'game_type' => $platformTypes[$val['gametype']]['type'],
                        'type_name' => $platformTypes[$val['gametype']]['type'],
                        'game_id' => $platformTypes[$val['gametype']]['id'],
                        'server_id' => 0,
                        'account' => $val['account'],
                        'bet' => $val['bet'],
                        'profit' => $val['win'] - $val['bet'],
                        'date' => $val['bettime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table($platformTypes[$val['gametype']]['table'])->where('round', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}