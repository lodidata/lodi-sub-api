<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class SEXYBCRT
 * @package Logic\GameApi\Error
 */
class SEXYBCRT extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'SEXYBCRT')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_sexybcrt')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'LIVE',
                        'order_number' => $val['platformTxId'],
                        'game_type' => 'SEXYBCRT',
                        'type_name' => 'SEXYBCRT',
                        'game_id' => 69,
                        'server_id' => 0,
                        'account' => $val['userId'],
                        'bet' => bcmul($val['betAmount'], 100, 0),
                        'profit' => bcmul($val['winAmount'] - $val['betAmount'], 100, 0),
                        'date' => $val['betTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_sexybcrt')->where('platformTxId', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}