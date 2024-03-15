<?php
namespace Model;

class ActiveRebetRecord extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'active_rebet_record';

    public $timestamps = false;

    protected $fillable = [
                            'id',
                            'rebet_id',
                            'rebet_name',
                            'rebet_date',
                            'stats_date',
                            'send_date',
                            'user_id',
                            'money',
                            'rebet_money',
                            'withdraw_require',
                            'today_valid_bet',
                            'valid_bet',
                            'state',
                            'updated_uid',
                        ];

    public static function boot() {

        parent::boot();

        // static::creating(function ($obj) {
        //     $obj->apply_time = date('Y-m-d H:i:s');
        // });
    }
}
