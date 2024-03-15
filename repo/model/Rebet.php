<?php

namespace Model;

class Rebet extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'rebet';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'win_money',
        'hall_level',
        'rebet',
        'bet_amount',
        'bet_number',
        'a_percent',
        'b_percent',
        'c_percent',
        'd_percent',
        'day',
        'type',
        'status',
        'plat_id',
        'batch_no',
        'dml_amount',
        'proportion_value',
        'process_time'
    ];

}


