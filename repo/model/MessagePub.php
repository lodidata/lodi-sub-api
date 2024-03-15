<?php

namespace Model;


class MessagePub extends \Illuminate\Database\Eloquent\Model {
    
    const DEFAULT_CURRENCY = 'CNY';

    protected $table = 'message_pub';

    public $timestamps = false;

    protected $fillable = [
                              'id',
                              'type',
                              'uid',
                              'm_id',
                              'status',
                              'created',
                              'updated',
                        ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->created = time();
            // $obj->type = 1;
        });
    }
}

