<?php

namespace Model;


class MailConfig extends \Illuminate\Database\Eloquent\Model {
    
    const DEFAULT_CURRENCY = 'CNY';

    protected $table = 'mail_config';

    public $timestamps = false;

    protected $fillable = [
                            'id',
                            'mailhost',
                            'mailport',
                            'mailname',
                            'mailpass',
                            'mailaddress',
                            'verification',
                            'is_ssl',
                            'created',
                            'updated',
                        ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->created = time();
            $obj->updated = time();
            // $obj->type = 1;
        });
    }
}

