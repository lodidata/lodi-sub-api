<?php
namespace Model;

class FundsDeposit extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'funds_deposit';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'user_id',
                          'trade_no',
                          'money',
                          'coupon_money',
                          'withdraw_bet',
                          'valid_bet',
                          'deposit_type',
                          'pay_type',
                          'pay_no',
                          'name',
                          'recharge_time',
                          'pay_bank_id',
                          'payment_id',
                          'pay_bank_info',
                          'receive_bank_account_id',
                          'receive_bank_info',
                          'memo',
                          'active_name',
                          'active_id',
                          'process_time',
                          'process_uid',
                          'created_uid',
                          'updated_uid',
                          'status',
                          'origin',
                          'state',
                          'marks',
                          'over_time',
                          'active_id_other',
                          'coupon_withdraw_bet',
                          'passageway_active',
                          'currency_type',
                          'coin_type',
                          'currency_name',
                          'currency_amount',
                          'rate',
                        ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->ip = \Utils\Client::getIp();
        });
    }
}



