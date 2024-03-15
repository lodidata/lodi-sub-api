<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class KMQM
 * @package Logic\GameApi\Error
 */
class KMQM extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'KMQM')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_kmqm')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'QP',
                        'order_number' => $val['ugsbetid'],
                        'game_type' => 'KMQM',
                        'type_name' => 'KMQM',
                        'game_id' => 77,
                        'server_id' => 0,
                        'account' => $val['userid'],
                        'bet' => bcmul($val['riskamt'], 100, 0),
                        'profit' => bcmul($val['winloss'], 100, 0),
                        'date' => $val['beton'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_kmqm')->where('ugsbetid', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}