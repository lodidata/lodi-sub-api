<?php

namespace Model;

use DB;

class TpLogin extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'tp_login';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'open_id',
        'avatar',
        'status',
        'bind_time',
        'type',
        'nick_name',
        'country',
        'province',
        'city',
        'union_id',
        'fromwhere',
    ];

    public static function boot() {
        parent::boot();
    }
}

