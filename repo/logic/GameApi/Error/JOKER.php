<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class JOKER
 * @package Logic\GameApi\Error
 */
class JOKER extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'Slot' => ['id' => 59, 'game' => 'GAME', 'type' => 'JOKER'],
            'Fishing' => ['id' => 60, 'game' => 'BY', 'type' => 'JOKERBY'],
            'ECasino' => ['id' => 61, 'game' => 'LIVE', 'type' => 'JOKERLIVE'],
        ];

        $menuList = [];
        $th3Data = $this->redis->get('gameapi_3th_menu:joker');
        if (!$th3Data) {
            $th3Data = \DB::table('game_3th')
                ->whereIn('game_3th.game_id', [59, 60, 61])
                ->select(['kind_id', 'type'])->get()->toArray();
            foreach ($th3Data as $val) {
                $val = (array)$val;
                $menuList[$val['kind_id']] = $val['type'];
            }
            $this->redis->set('gameapi_3th_menu:joker', json_encode($menuList, JSON_UNESCAPED_UNICODE));
        } else {
            $menuList = json_decode($th3Data, true);
        }
        
        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'JOKER')
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
                $is_insert = \DB::table('game_order_joker')->insert($val);
                if ($is_insert) {
                    $type = $menuList[$val['gameCode']] ?: 'Slot';
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$type]['game'],
                        'order_number' => $val['OCode'],
                        'game_type' => $platformTypes[$type]['type'],
                        'type_name' => $platformTypes[$type]['type'],
                        'game_id' => $platformTypes[$type]['id'],
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
                if(\DB::table('game_order_joker')->where('OCode', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}