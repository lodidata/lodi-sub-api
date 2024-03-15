<?php

namespace Model;

class BankAccount extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'bank_account';

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $fillable = [
        'bank_id',
        'creater',
        'name',
        'card',
        'address',
        'total',
        'today',
        'limit_max',
        'limit_once_min',
        'limit_once_max',
        'limit_day_max',
        'sort',
        'state',
        'created_uid',
        'created',
        'updated',
        'usage',
        'qrcode',
        'comment',
        'type',
    ];

}