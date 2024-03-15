<?php
namespace Model;
use DB;
class LotteryOrderTemp extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'lottery_order_temp';

    public $timestamps = false;
    
    protected $fillable = [  
                          'id',
                          'user_id',
                          'user_name',
                          'order_number',
                          'lottery_id',
                          'lottery_number',
                          'play_id',
                          'pay_money',
                          'play_group',
                          'play_name',
                          'play_number',
                          'odds',
                          'bet_num',
                          'chase_number',
                          'chase_amount',
                          'one_money',
                          'hall_id',
                          'room_id',
                          'origin',
                          'times',
                          'state',
                          'marks',
                          // 'created',
                          // 'updated',
                          'odds_ids',
                          'settle_odds',
                          'win_bet_count',
                          'rake_back',
                        ];


    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->odds = is_array($obj->odds) ? json_encode($obj->odds, JSON_UNESCAPED_UNICODE) : $obj->odds;
            $obj->settle_odds = is_array($obj->settle_odds) ? json_encode($obj->settle_odds, JSON_UNESCAPED_UNICODE) : $obj->settle_odds;
            $obj->odds_ids = is_array($obj->odds_ids) ? json_encode($obj->odds_ids, JSON_UNESCAPED_UNICODE) : $obj->odds_ids;
        });
    }
}