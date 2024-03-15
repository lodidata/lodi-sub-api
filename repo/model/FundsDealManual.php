<?php
namespace Model;
use DB;
class FundsDealManual extends \Illuminate\Database\Eloquent\Model {

    const MANUAl_DEPOSIT                = 1; //手动存款
    const MANUAl_DECREASE_MONEY         = 2; //手动扣款
    const MANUAl_DISCOUNT_AMOUNT        = 3; //手动发放优惠
    const MANUAl_DECREASE_BALANCE       = 4; //手动减少余额
    const MANUAl_ADDMONEY               = 5; //手动增加余额
    const MANUAl_REBET_MONEY            = 6; //手动发放返水
    const MANUAl_SUB_TO_MASTER_WALLET   = 7; //手动子转主钱包
    const MANUAl_MASTER_TO_SUB_WALLET   = 8; //手动主转子钱包
    const MANUAl_INCREASE_BET           = 9; //手动增加打码量
    const MANUAl_DECREASE_BET           = 10; //手动减少打码量
    const MANUAl_INCREASE_FREEMONEY     = 11; //手动增加可提余额
    const MANUAl_DECREASE_FREEMONEY     = 12; //手动减少可提余额
    const WITHDRAWAL_INCREASE_SHARE     = 13; //股东分红增加可提余额
    const WITHDRAWAL_DECREASE_SHARE     = 14; //股东分红减少可提余额
    const MANUAl_INCREASE_DIRECT        = 15; //增加直推余额
    const MANUAl_DECREASE_DIRECT        = 16; //减少直推余额

    protected $table = 'funds_deal_manual';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'user_id',
                          'username',
                          'user_type',
                          'type',
                          'trade_no',
                          'operator_type',
                          'front_money',
                          'money',
                          'balance',
                          'withdraw_bet',
                          'valid_bet',
                          'admin_uid',
                          'bank_id',
                          'ip',
                          'wallet_type',
                          'sub_type',
                          'confirm_time',
                          'memo',
                          'active_id',
                          'created',
                          'updated',
                          'status',
                        ];

    public static function boot() {
        parent::boot();

        static::creating(function ($obj) {
           $obj->created = time();
           $obj->updated = time();
           $obj->ip = \Utils\Client::getIp();
        });

    }
}