<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class PP
 * @package Logic\GameApi\Error
 */
class PP extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'Slot' => ['id' => 64, 'game' => 'GAME', 'type' => 'PP'],
            'Fishing' => ['id' => 65, 'game' => 'BY', 'type' => 'PPBY'],
            'ECasino' => ['id' => 66, 'game' => 'LIVE', 'type' => 'PPLIVE'],
        ];
        $menuList = [];
        $th3Data = $this->redis->get('gameapi_3th_menu:pp');
        if (!$th3Data) {
            $th3Data = \DB::table('game_3th')
                ->whereIn('game_3th.game_id', [64, 65, 66])
                ->select(['kind_id', 'type'])->get()->toArray();
            foreach ($th3Data as $val) {
                $val = (array)$val;
                $menuList[$val['kind_id']] = $val['type'];
            }
            $this->redis->set('gameapi_3th_menu:pp', json_encode($menuList, JSON_UNESCAPED_UNICODE));
        } else {
            $menuList = json_decode($th3Data, true);
        }
        unset($th3Data, $val);
        
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'PP')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_pp')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$menuList[$val['gameCode']]]['game'],
                        'order_number' => $val['OCode'],
                        'game_type' => $platformTypes[$menuList[$val['gameCode']]]['type'],
                        'type_name' => $platformTypes[$menuList[$val['gameCode']]]['type'],
                        'game_id' => $platformTypes[$menuList[$val['gameCode']]]['id'],
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
                if(\DB::table('game_order_pp')->where('OCode', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}