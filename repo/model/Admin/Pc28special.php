<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 18:51
 */

namespace Model\Admin;


class Pc28special extends LogicModel {
    protected $table = 'pc28special';
    public $timestamps = false;


    public $desc_attributes=[
        'odds'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '赔率(总注<门槛1)'
        ],
        'desc'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '说明'
        ],
        'odds1'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '赔率(门槛1≦总注≦门槛2)'
        ],
        'step1'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '门槛1'
        ],
        'odds2'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '赔率（总注>门槛2）'
        ],
        'step2'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '门槛2'
        ]
    ];


}