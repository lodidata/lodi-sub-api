<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */
namespace Model;
// use Illuminate\Support\Facades\DB;

class TrialFunds extends \Illuminate\Database\Eloquent\Model {

    const DEFAULT_CURRENCY = 'CNY';

    protected $table = 'trial_funds';

    public $timestamps = false;

    protected $fillable = [
        'balance_before',
        'balance',
        'uuid',
        'comment',
        'last_ip'
    ];

    public static function boot() {
        parent::boot();
        static::creating(function ($obj) {
            $obj->last_ip = \Utils\Client::getIp();
            $obj->comment = $obj->comment.'的主钱包';
            $obj->uuid = \DB::raw('uuid()');
            $obj->balance = 0;
            $obj->balance_before = 0;
        });
    }
}