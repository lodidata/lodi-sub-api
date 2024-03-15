<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class FC
 * @package Logic\GameApi\Error
 */
class FC extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            2 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
            1 => ['id' => 94, 'game' => 'BY', 'type' => 'FCBY'],
            7 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
        ];
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'FC')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_fc')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$val['gametype']]['game'],
                        'order_number' => $val['recordID'],
                        'game_type' => $platformTypes[$val['gametype']]['type'],
                        'type_name' => $platformTypes[$val['gametype']]['type'],
                        'game_id' => $platformTypes[$val['gametype']]['id'],
                        'server_id' => 0,
                        'account' => $val['account'],
                        'bet' => bcmul($val['bet'], 100, 0),
                        'profit' => bcmul($val['prize'], 100, 0),
                        'date' => $val['bdate'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_fc')->where('recordID', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}