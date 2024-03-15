<?php
/**
 * 额度信息展示
 * @author Taylor 2019-01-21
 */

use Logic\Admin\BaseController;

return new class extends BaseController
{

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {

        $res = \DB::table('rpt_order_amount')
            ->whereNotIn('game_type', ['ZYCPSTA', 'ZYCPCHAT'])
            ->selectRaw("count_date, sum(game_bet_amount) as total_bet,sum(game_prize_amount) as total_prize,clear_status,count_date")
            ->groupBy('count_date')
            ->get()
            ->toArray();
        $dataInfo = [];
        foreach ($res as $key => &$value) {
            $value = (array)$value;
            $value['count_date'] = $value['count_date'];
            $value['clear_status'] = $value['clear_status'];
            $value['quota'] = bcsub($value['total_bet'], $value['total_prize'], 2);
            array_push($dataInfo, $value);
        }

        $info = DB::table('rpt_order_amount')
            ->whereNotIn('game_type', ['ZYCPSTA', 'ZYCPCHAT'])
            ->orderBy('id', 'DESC')
            ->get([
                'game_type',
                'game_name', 'count_date', 'clear_status',
                \DB::raw('game_bet_amount-game_prize_amount as quota'),
            ]);

        $data = [];
        foreach ($dataInfo as $datum) {
            $datum = (array)$datum;
            $itemArr = [];
            foreach ($info as $key => $item) {
                $item = (array)$item;
                if ($datum['count_date'] == $item['count_date']) {
                    $item['count_date'] = $item['count_date'];
                    $item['clear_status'] = $item['clear_status'];
                    $item['quota'] = $item['quota'];
                    array_push($itemArr, $item);
                }
            }
            $datum['info'] = $itemArr;
            array_push($data, $datum);
        }

        array_multisort(array_column($data, 'count_date'), SORT_DESC, $data);
        return $this->lang->set(0, [], $data);
    }
};