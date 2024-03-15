<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 18:31
 */

namespace Model;

class SendPrize extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'send_prize';

    public $timestamps = false;
    
    protected $fillable = ['user_id',
                            'user_name',
                            'bet_number',
                            'order_number',
                            'odds_id',
                            'bet_type',
                            'pay_money',
                            'earn_money',
                            'money',
                            'rebet',
                            'lose_earn',
                            'status'
                        ];
}

