<?php
namespace Model;

class FundsTransferLog extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'funds_transfer_log';

    public $timestamps = false;

    protected $fillable = [
                            'id',
                            'user_id',
                            'username',
                            'out_id',
                            'out_name',
                            'out_type',
                            'in_id',
                            'in_name',
                            'in_type',
                            'type',
                            'amount',
                            'no',
                            'opater_uid',
                            'status',
                            'memo',
                        ];

    public static function boot() {
        parent::boot();
    }
}