<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:26
 */

namespace Model;

class FundsChild extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'funds_child';

    public $timestamps = false;

    protected $fillable = [
                            'last_balance',
                            'balance',
                            'uuid',
                            'name',
                            'pid',
                            'game_type',
                            'status',
                        ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->uuid = \DB::raw('uuid()');
            $obj->balance = 0;
            $obj->last_balance = 0;
            $obj->status = 'enabled';
        });
    }
}

