<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model\Admin;

class LotteryPlayStruct extends LogicModel {
    protected $table = 'lottery_play_struct';
    public $timestamps = false;

    public $desc_attributes=[
        'open'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                true => '开启',
                false => '关闭'
            ]
        ],
        'buy_ball_num'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '购球数'
        ]
    ];

}