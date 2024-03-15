<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class UG
 * @package Logic\GameApi\Error
 */
class BNG extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'BNG')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_bng')->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'GAME',
                        'order_number' => $val['round'],
                        'game_type' => 'BNG',
                        'type_name' => 'BNG',
                        'game_id' => 99,
                        'server_id' => 0,
                        'account' => $val['Username'],
                        'bet' => $val['betAmount'],
                        'profit' => $val['income'],
                        'date' => $val['gameDate'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_bng')->where('round', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}