<?php

namespace Model\Admin;

class ActiveBkge extends LogicModel {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'active_bkge';
    public $timestamps = true;


    public $title_param = 'name';          //  新增和删除时 需要说明是删除新增哪个，对应表中的某例

    public $desc_attributes = [
        'name' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '返佣活动名',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'stime' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '开始时间',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'etime' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '结束时间',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'bkge_date' => [  //0禁用1启用2黑名单3删除4封号
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                'day' => '每日',
                'week' => '每周',
                'month' => '每月',
            ]
        ],
        'bkge_time' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '返佣时间点',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'condition_opt' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '返佣条件',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'data_opt' => [
            'multi' => false,
            'value' => true,  //值放备注中true 写在备注小（）内
            'key' => '返佣数据规则',
            'decrypt' => true,  //当value为true时才需要用到该参数,说明该字段是加密存储的
        ],
        'status' => [
            'multi' => false,
            'value' => false,  //结果值不带上去
            'key' => [
                'disabled' => '关闭',
                'enabled' => '启用',
            ]
        ],
    ];


}