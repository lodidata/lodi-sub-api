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
            1 => ['id' => 62, 'game' => 'GAME', 'type' => 'JILI'],
            5 => ['id' => 63, 'game' => 'BY', 'type' => 'JILIBY'],
        ];
        
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'JILI')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            $wagersId = $val['GameCategoryId'];
            if ($wagersId != 5) {
                $wagersId = 1;
            }
            try {
                $is_insert = \DB::table('game_order_jili')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$wagersId]['game'],
                        'order_number' => $val['OCode'],
                        'game_type' => $platformTypes[$wagersId]['type'],
                        'type_name' => $platformTypes[$wagersId]['type'],
                        'game_id' => $platformTypes[$wagersId]['id'],
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
                if(\DB::table('game_order_jili')->where('OCode', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}