<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;
use DB;
class LotteryChaseOrderSub extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_chase_order_sub';

    public $timestamps = false;
    
    protected $fillable = [  
                        'chase_number',
                        'order_number',
                        'lottery_number',
                        'pay_money',
                        'play_number',
                        'odds',
                        'bet_num',
                        'times',
                        'odds_ids',
                        'settle_odds',
                        'win_bet_count',
                        'rake_back',
                        'send_money',
                        'lose_earn',
                        'open_code',
                        'state',
                        ];


    /**
     * 查看追号子订单
     * @param $condition
     * @return mixed [type]            [description]
     */
    private static function getRecord($condition) {
        $table_name = 'lottery_chase_order_sub';
        $query = DB::table($table_name)
            ->where('chase_number', '=', $condition['order_number']);

        $select = [
            // id
            'id' => $table_name . '.id',
            // 期号
            'lottery_number' => $table_name . '.lottery_number',
            // 投注额
            'pay_money' => $table_name . '.pay_money',
            // 中奖金额
            'send_money' => $table_name . '.send_money',
            // 倍数
            'times' => $table_name . '.times',
            // 赔率
            'odds' => $table_name . '.odds',
            // 开奖号码
            'open_code' => $table_name . '.open_code',
            // 状态
            'state' => $table_name . '.state',
            // 选号
            'play_number' => $table_name . '.play_number',
        ];
        return $query->select(array_values($select));
    }


    /**
     * 查看追号子订单记录
     *
     * @param  [type]  $condition [description]
     * @return mixed [type]            [description]
     */
    public static function getRecords($condition) {
        $query = self::getRecord($condition);
        return $query->get()->toArray();
    }
}

