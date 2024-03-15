<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */
namespace Model;
// use Illuminate\Support\Facades\DB;

class FundsTrial extends \Illuminate\Database\Eloquent\Model {

    const DEFAULT_CURRENCY = 'CNY';

    protected $table = 'funds';

    public $timestamps = false;

    protected $fillable = [
        'balance_before',
        'balance',
        'name',
        'uuid',
        'comment',
        'currency',
        'last_ip'
    ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $obj->last_ip = \Utils\Client::getIp();
            $obj->currency = self::DEFAULT_CURRENCY;
            $obj->comment = $obj->comment.'的主钱包';
            $obj->name = '主钱包';
            $obj->uuid = \DB::raw('uuid()');
            $obj->balance = 0;
            $obj->balance_before = 0;
        });
    }
}