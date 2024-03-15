<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class EVO
 * @package Logic\GameApi\Error
 */
class EVO extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'EVO')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_evo')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'LIVE',
                        'order_number' => $val['OCode'],
                        'game_type' => 'EVO',
                        'type_name' => 'EVO',
                        'game_id' => 67,
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
                if(\DB::table('game_order_evo')->where('OCode', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}