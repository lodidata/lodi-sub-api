<?php
namespace Model;

class ActiveApply extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'active_apply';

    public $timestamps = false;

    protected $fillable = [
                              'id',
                              'user_id',
                              'user_name',
                              'mobile',
                              'email',
                              'active_id',
                              'active_name',
                              'valid_bet',
                              'deposit_money',
                              'coupon_money',
                              'withdraw_require',
                              'apply_time',
                              'process_time',
                              'content',
                              'memo',
                              'created_uid',
                              'status',
                              'state',
                        ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->apply_time = date('Y-m-d H:i:s');
        });
    }
}
