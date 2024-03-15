<?php
namespace Logic\GameApi\Error;

use Logic\GameApi\GameApi;
use Logic\Logic;

/**
 * game_order_error表操作
 * Class CG
 * @package Logic\GameApi\Error
 */
class CG extends Logic
{
    public function addOrder()
    {
        $platformTypes = [
            'slot' => ['id' => 91, 'game' => 'GAME', 'type' => 'CG'],
            'pvp' => ['id' => 92, 'game' => 'QP', 'type' => 'CGQP'],
        ];
        $menuList = [];
        $th3Data = $this->redis->get('gameapi_3th_menu:cg');
        if (!$th3Data) {
            $th3Data = \DB::table('game_3th')
                ->whereIn('game_3th.game_id', [91, 92])
                ->select(['kind_id', 'type'])->get()->toArray();
            foreach ($th3Data as $val) {
                $val = (array)$val;
                $menuList[$val['kind_id']] = $val['type'];
            }
            $this->redis->set('gameapi_3th_menu:cg', json_encode($menuList, JSON_UNESCAPED_UNICODE));
        } else {
            $menuList = json_decode($th3Data, true);
        }
        unset($th3Data, $val);

        $error_list = \DB::table('game_order_error')
            ->where('game_type', 'CG')
            ->limit(1000)
            ->get(['id','order_number', 'json'])->toArray();
        foreach ($error_list as $value) {
            $value = (array)$value;
            $val = json_decode($value['json'], true);
            try {
                $is_insert = \DB::table('game_order_cg')->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => $platformTypes[$menuList[$val['GameType']]]['game'],
                        'order_number' => $val['SerialNumber'],
                        'game_type' => $platformTypes[$menuList[$val['GameType']]]['type'],
                        'type_name' => $platformTypes[$menuList[$val['GameType']]]['type'],
                        'game_id' => $platformTypes[$menuList[$val['GameType']]]['id'],
                        'server_id' => 0,
                        'account' => $val['ThirdPartyAccount'],
                        'bet' => bcmul($val['BetMoney'], 100, 0),
                        'profit' => bcmul($val['MoneyWin'] - $val['BetMoney'], 100, 0),
                        'date' => $val['LogTime'],
                    ]);
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            } catch (\Exception $e) {
                if(\DB::table('game_order_cg')->where('SerialNumber', $value['order_number'])->count()){
                    \DB::table('game_order_error')
                        ->where('id', $value['id'])->delete();
                }
            }
        }
    }
}