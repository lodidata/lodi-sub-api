<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;

use DB;

class LotteryTrialOrder extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'lottery_trial_order';

    public $timestamps = false;

    protected $fillable = [
        // 'id',
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
        'open_code',
        'send_money',
        'lose_earn',
    ];


    public static function boot()
    {

        parent::boot();

        static::creating(
            function ($obj) {
                $obj->odds = is_array($obj->odds) ? json_encode($obj->odds, JSON_UNESCAPED_UNICODE) : $obj->odds;
                $obj->settle_odds = is_array($obj->settle_odds) ? json_encode(
                    $obj->settle_odds, JSON_UNESCAPED_UNICODE
                ) : $obj->settle_odds;
                $obj->odds_ids = is_array($obj->odds_ids) ? json_encode(
                    $obj->odds_ids, JSON_UNESCAPED_UNICODE
                ) : $obj->odds_ids;
            }
        );

        // static::updating(function ($obj) {
        //     $obj->win_bet_count = is_array($obj->win_bet_count) ? json_encode($obj->win_bet_count, JSON_UNESCAPED_UNICODE) : $obj->win_bet_count;
        // });
    }

    // public static function createOrder($order) {
    //     $order2 = [];
    //     foreach ($order as $key => $value) {
    //         $temp = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    //         $order2[$key] = $temp;
    //     }
    //     return self::create($order2);
    // }

    public static function generateOrderNumber($rand = 999999999, $length = 9)
    {
        return date('ndhis') . str_pad(mt_rand(1, $rand), $length, '0', STR_PAD_LEFT);
    }

    /**
     * 查询试玩订单记录
     *
     * @param  [type]  $condition [description]
     * @param  integer $page [description]
     * @param  integer $pageSize [description]
     *
     * @return [type]            [description]
     */
    public static function getTrialRecords($condition, $page = 1, $pageSize = 20)
    {
        global $app;
        $table = DB::table('lottery_trial_order')
                   ->leftjoin('lottery', 'lottery.id', '=', 'lottery_trial_order.lottery_id')
                   ->leftjoin(
                       'send_trial_prize', 'send_trial_prize.order_number', '=', 'lottery_trial_order.order_number'
                   )
                   ->leftjoin('hall', 'hall.id', '=', 'lottery_trial_order.hall_id')
                   ->orderBy('lottery_trial_order.id', 'desc');
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table = $table->whereRaw("find_in_set('winning', lottery_trial_order.state)");
                    break;
                case 'lose':
                    $table = $table->whereRaw(
                        "find_in_set('open', lottery_trial_order.state) AND !find_in_set('winning', lottery_trial_order.state) "
                    );
                    break;
                default:
                    $table = $table->whereRaw("!FIND_IN_SET('open',lottery_trial_order.state)");
                    break;
            }
        }

        isset($condition['id']) && $table = $table->where('lottery_trial_order.id', $condition['id']);
        isset($condition['user_id']) && $table = $table->where('lottery_trial_order.user_id', $condition['user_id']);
        isset($condition['lottery_number']) &&
        $table = $table->where('lottery_trial_order.lottery_number', $condition['lottery_number']);
        isset($condition['order_number']) &&
        $table = $table->where('lottery_trial_order.order_number', $condition['order_number']);
        isset($condition['lottery_id']) &&
        $table = $table->where('lottery_trial_order.lottery_id', $condition['lottery_id']);
        isset($condition['start_time']) &&
        $table = $table->where('lottery_trial_order.created', '>=', $condition['start_time']);
        isset($condition['end_time']) &&
        $table = $table->where('lottery_trial_order.created', '<=', $condition['end_time']);
        isset($condition['room_id']) && $table = $table->where('lottery_trial_order.room_id', $condition['room_id']);

        $select = [
            'id'             => 'lottery_trial_order.id',
            'hall_id'        => 'lottery_trial_order.hall_id',
            'room_id'        => 'lottery_trial_order.room_id',
            'created'        => 'lottery_trial_order.created',
            'order_number'   => \DB::raw("concat(lottery_trial_order.order_number,'') as order_number "),
            'user_name'      => 'lottery_trial_order.user_name',
            'lottery_id'     => 'lottery_trial_order.lottery_id',
            'origin'         => 'lottery_trial_order.origin',
            'lottery_number' => 'lottery_trial_order.lottery_number',
            'lottery_name'   => DB::raw('lottery.name AS lottery_name'),
            'pid'            => 'lottery.pid',
            'play_group'     => 'lottery_trial_order.play_group',
            'lottery_order'  => 'lottery_trial_order.play_name',
            'pay_money'      => 'lottery_trial_order.pay_money',
            'bet_num'        => 'lottery_trial_order.bet_num',
            'one_money'      => 'lottery_trial_order.one_money',
            'times'          => 'lottery_trial_order.times',
            'odds'           => 'lottery_trial_order.odds',
            'win_bet_count'  => 'lottery_trial_order.win_bet_count',
            'play_number'    => 'lottery_trial_order.play_number',
            'send_money'     => DB::raw('send_trial_prize.money as send_money'),
            'play_id'        => 'lottery_trial_order.play_id',
            'hall_name'      => 'hall.hall_name',
            'hall_level'     => 'hall.hall_level',
            'state'          => 'lottery_trial_order.state',
            'user_id'        => 'lottery_trial_order.user_id',
            'open_img'       => 'lottery.open_img',
        ];

        if (isset($condition['info'])) {
            $select['period_code'] = DB::raw(
                '(SELECT period_code FROM lottery_info WHERE lottery_number = lottery_trial_order.lottery_number AND lottery_type = lottery_trial_order.lottery_id) AS period_code'
            );
        }

        $table = $table->where('chase_number', '<=', 0);
        $table = $table->select(array_values($select));
        //       echo $table->toSql();exit;
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }

    public static function getGame($condition, $page = 1, $pageSize = 20)
    {
        $table = DB::table('lottery_order')->leftjoin(
            'send_prize', function ($join) {
            $join->on('lottery_order.order_number', '=', 'send_prize.order_number');
        }
        )->join('lottery', 'lottery.id', '=', 'lottery_order.lottery_id')
                   ->groupBy('lottery_order.id')
                   ->selectRaw(
                       'lottery.name, count(lottery_order.id) as bet_times, sum(lottery_order.pay_money) as bet_money, sum(send_prize.pay_money) as valid_bet, sum(earn_money) as money'
                   );

        isset($condition['start_time']) &&
        $table = $table->where('lottery_order.created', '>=', $condition['start_time']);
        isset($condition['end_time']) && $table = $table->where('lottery_order.created', '<=', $condition['end_time']);
        isset($condition['user_id']) && $table = $table->where('lottery_order.user_id', $condition['user_id']);
        isset($condition['lottery_id']) && $table = $table->where('lottery_order.lottery_id', $condition['lottery_id']);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }

    public static function getOrigin($pl)
    {
        $pls = ['pc' => 1, 'h5' => 2, 'android' => 3, 'ios' => 3];
        return isset($pls[$pl]) ? $pls[$pl] : $pls['pc'];
    }
}
