<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model\Admin;

class Hall extends LogicModel
{
    protected $table = 'hall';
    public $timestamps = false;

    public $desc_attributes=[
        'min_bet'=>[
            'multi' => false,   //该字段是否是可多选，
            'value' => true,  //值不放备注中无视
            'unit'=>true,//金额单位，true是元，false是分
            'key' => '最小投注限额'
        ]
    ];

}