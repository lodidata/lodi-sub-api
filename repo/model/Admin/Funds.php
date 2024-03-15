<?php

namespace Model\Admin;

class Funds extends LogicModel
{

    protected $table = 'funds';

    public $timestamps = false;

    public $title_param = 'name';

    public $desc = [
        'password' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'decrypt' => true,
            'key' => '支付密码',
        ],
        'freeze_password' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'decrypt' => true,
            'key' => '保险箱密码',
        ],
    ];

}