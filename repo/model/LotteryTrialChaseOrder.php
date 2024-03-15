<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;
use DB;
class LotteryTrialChaseOrder extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_trial_chase_order';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'chase_type',
        'chase_number',
        'hall_id',
        'hall_name',
        'room_id',
        'room_name',
        'lottery_id',
        'lottery_name',
        'play_group',
        'play_name',
        'current_bet',
        'increment_bet',
        'complete_periods',
        'sum_periods',
        'reward',
        'profit',
        'mode',
        'state',
        'created',
        'updated',
        'origin',
    ];

}

