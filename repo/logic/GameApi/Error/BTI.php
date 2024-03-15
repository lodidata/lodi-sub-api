<?php

namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class BTI
 * @package Logic\GameApi\Error
 */
class BTI extends Logic
{
    public function addOrder()
    {
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'BTI')
            ->limit(1000)
            ->get(['id', 'order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_bti')->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'SPORT',
                        'order_number' => $val['PurchaseID'],
                        'game_type' => 'BTI',
                        'type_name' => 'BTI',
                        'game_id' => 135,
                        'server_id' => 0,
                        'account' => $val['MerchantCustomerID'],
                        'bet' => bcmul($val['ValidStake'], 100, 0),
                        'profit' => bcmul($val['PL'], 100, 0),
                        'date' => $val['CreationDate'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if (\DB::table('game_order_bti')->where('PurchaseID', $value['order_number'])->count()) {
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}