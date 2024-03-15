<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class ServiceAccount extends LogicModel {

//    protected $dateFormat = 'Y-m-d H:i:s';

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'service_account';

    public $title_param = 'account';
    public $title_name = '客服账号';

    public $desc = [
        'status' => [  //2禁用1启用
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                2 => '禁用',
                1 => '启用',

            ]
        ],
        'type' => [  //账号类型
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                'qq' => 'QQ',
                'wechat' => '微信',

            ]
        ],
        'account' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '账号',
        ],
        'name' => [
            'multi' => false,
            'value' => false,  //结果值不带上去
            'key' => '昵称'
        ],
        'avatar' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '头像',
            'decrypt' => true,
        ],

    ];


}