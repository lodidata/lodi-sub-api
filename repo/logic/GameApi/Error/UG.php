<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class UG
 * @package Logic\GameApi\Error
 */
class UG extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'UG')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_ug')->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'SPORT',
                        'order_number' => $val['BetID'],
                        'game_type' => 'UG',
                        'type_name' => 'UG',
                        'game_id' => 95,
                        'server_id' => 0,
                        'account' => $val['Account'],
                        'bet' => bcmul($val['BetAmount'], 100, 0),
                        'profit' => bcmul($val['Win'], 100, 0),
                        'date' => $val['BetDate'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_ug')->where('BetID', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}