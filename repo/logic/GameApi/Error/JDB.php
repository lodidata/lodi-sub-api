<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class JDB
 * @package Logic\GameApi\Error
 */
class JDB extends Logic
{
    public function addOrder()
    {
        $orderTable = [
            0 => 'game_order_jdb_dz',
            7 => 'game_order_jdb_by',
            9 => 'game_order_jdb_jj',
            12 => 'game_order_jdb_jj',
            18 => 'game_order_jdb_qp',
        ];

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'JDB')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            $parentType = 'GAME';
            $gameType = 'JDB';
            $game_id = 70;
            switch ($val['gType']) {
                case 0:
                    $parentType = 'GAME';
                    $gameType = 'JDB';
                    $game_id = 70;
                    break;
                case 9:
                case 12:
                    $parentType = 'ARCADE';
                    $gameType = 'JDBJJ';
                    $game_id = 97;
                    break;
                case 7:
                    $parentType = 'BY';
                    $gameType = 'JDBBY';
                    $game_id = 71;
                    break;
                case 18:
                    $parentType = 'QP';
                    $gameType = 'JDBQP';
                    $game_id = 87;
                    break;
            }
            try {
                $is_insert = \DB::table($orderTable[$val['gType']])->insert($val);
                if ($is_insert) {
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $parentType,
                        'order_number' => $val['seqNo'],
                        'game_type' => $gameType,
                        'type_name' => $gameType,
                        'game_id' => $game_id,
                        'server_id' => $val['mtype'],
                        'account' => $val['playerId'],
                        'bet' => -$val['bet'],
                        'profit' => $val['total'],
                        'date' => $val['gameDate'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table($orderTable[$val['gType']])->where('seqNo', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}