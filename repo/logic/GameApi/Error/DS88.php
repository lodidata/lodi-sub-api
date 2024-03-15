<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class DS88
 * @package Logic\GameApi\Error
 */
class DS88 extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'DS88')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_ds88')->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'SABONG',
                        'order_number' => $val['slug'],
                        'game_type' => 'DS88',
                        'type_name' => 'DS88',
                        'game_id' => 100,
                        'server_id' => 0,
                        'account' => $val['account'],
                        'bet' => bcmul($val['bet_account'], 100, 0),
                        'profit' => bcmul($val['net_income'], 100, 0),
                        'date' => $val['settled_at'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_ds88')->where('BetID', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}