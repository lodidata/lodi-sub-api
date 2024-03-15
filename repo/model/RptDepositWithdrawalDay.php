<?php
namespace Model;
class RptDepositWithdrawalDay extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'rpt_deposit_withdrawal_day';

    public $timestamps = false;

    //protected $fillable = [];

    public static function boot() {

        parent::boot();

    }
}

