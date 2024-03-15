<?php

namespace Model\Admin;

class User extends LogicModel {
    protected $table = 'user';

    public $timestamps = false;
    public $title_param = 'name';          //  新增和删除时 需要说明是删除新增哪个，对应表中的某例

    public $desc_attributes = [
        'state' => [  //0禁用1启用2黑名单3删除4封号
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                0 => '禁用',
                1 => '启用',
                2 => '黑名单',
                3 => '删除',
                4 => '封号',
            ]
        ],
        'auth_status' => [
            'multi' => true,   //该字段是否是可多选，
            'value' => false,  //值不放备注中无视
            'key' => [         //当  multi为true时表示 可以是多选，选中的定义为 对应key下的值的0 不选中为1
                'refuse_withdraw'       => ['禁止提款','允许提款'],
                'refuse_sale'           => ['禁止优惠','允许优惠'],
                'refuse_rebate'         => ['禁止返水','允许返水'],
                'refuse_bkge'           => ['禁止返佣','允许返佣'],
            ]
        ],
        'mobile' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '手机号',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'agent_switch' => [
            'multi' => false,
            'value' => false,  //结果值不带上去
            'key' => [
                0 => '关闭代理',
                1 => '打开代理',
            ]
        ],
        'email' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '邮箱',
            'decrypt' => true,
        ],
        'wechat' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '微信',
            'decrypt' => true,
        ],
        'tags' => [
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '标签',
            'table' => 'label',  //当value为true时才需要用到该参数,说明该字段存储的值与另外一张表关联
            't_id' => 'id', //当有table时才需要用到该参数-必有,说明该字段存储的值与另外一张表关联的字段
            't_val' => 'title', //当有table时才需要用到该参数-必有,说明该字段存储的值与另外一张表关联需要存储的字段值
        ],
        'password' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => '更改登陆密码',
        ],
    ];
}

