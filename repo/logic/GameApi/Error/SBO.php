<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class UG
 * @package Logic\GameApi\Error
 */
class SBO extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'SBO')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_sbo')->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'SPORT',
                        'order_number' => $val['refNo'],
                        'game_type' => 'SBO',
                        'type_name' => 'SBO',
                        'game_id' => 72,
                        'server_id' => 0,
                        'account' => $val['username'],
                        'bet' => bcmul($val['stake'], 100, 0),
                        'profit' => bcmul($val['winlost'], 100, 0),
                        'date' => $val['orderTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_sbo')->where('refNo', $value['refNo'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}