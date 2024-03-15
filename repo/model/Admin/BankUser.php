<?php
namespace Model\Admin;

class BankUser extends LogicModel {


    protected $table = 'bank_user';

    public $timestamps = false;

    public $title_param = 'card';
    public $title_name = '卡号';
    public $title_decrypt = true;

    public $desc = [
        'name' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '户名',
        ],
        'card' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'decrypt' => true,
            'key' => '银行卡号',
        ],
        'address' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '开户行地址',
        ],
        'state' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [
                'disabled' => '停用',
                'enabled' => '启用',
                'delete' => '删除',
            ],
        ],
    ];
}


