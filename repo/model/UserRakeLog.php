<?php
namespace Model;

class UserRakeLog extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'user_rake_log';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'user_id',
                          'user_name',
                          'order_user_id',
                          'order_user_name',
                          'wallet_id',
                          'order_number',
                          'back_money',
                          'withdraw_bet',
                          'state',
                          'type',
                          'created',
                          'updated',
                        ];
                          
    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->created = time();
        });

        // static::updating(function ($obj) {
        //     $obj->update = time();
        // });
    }  
}


