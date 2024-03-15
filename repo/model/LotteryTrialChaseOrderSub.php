<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;
use DB;
class LotteryTrialChaseOrderSub extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_trial_chase_order_sub';

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
        'send_money',
        'lose_earn',
        'open_code',
        'state',
    ];

}

