<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model;

class FundsOrderDealLog extends \Illuminate\Database\Eloquent\Model {
    protected $table      = 'funds_order_deal_log';
    //protected $connection = 'slave';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'user_type',
        'username',
        'deal_number',
        'order_number',
        'deal_type',
        'deal_category',
        'deal_money',
        'coupon_money',
        'balance',
        'memo',
        'wallet_type',
        'status',
        // 'created'
        'withdraw_bet',
        'total_bet',
        'total_require_bet',
        'free_money',
        'admin_user',
        'admin_id',
    ];
    public static function boot() {
        global $playLoad;

        parent::boot();

        static::creating(function ($obj) use ($playLoad) {
            $obj->created = date('Y-m-d H:i:s');
            $obj->deal_number = FundsDealLog::generateDealNumber();
            $obj->coupon_money = $obj->coupon_money ?? 0;
            $obj->status = 1;
            $obj->user_type = empty($obj->user_type) ? 1 : $obj->user_type; // 代理改人人代理默认1
            $obj->admin_id = $playLoad['uid'] ?? 0;
            $obj->admin_user = $playLoad['nick'] ?? '';
        });
    }
}