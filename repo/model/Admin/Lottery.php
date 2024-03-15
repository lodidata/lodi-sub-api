<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class Lottery extends LogicModel {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'lottery';



    public static function getById($id){

        return self::find($id);

    }

    public $desc_attributes=[
        'state'=>[
            'multi' => true,   //该字段是否是可多选，
            'value' => false,  //值不放备注中无视
            'key' => [         //当  multi为true时表示 可以是多选，选中的定义为 对应key下的值的0 不选中为1
                'standard' => ['开启传统模式','关闭传统模式'],
                'chat' => ['开启房间模式','关闭房间模式'],
                'enabled' => ['开启总开关','关闭总开关']
            ]
        ],
        'all_bet_max'=>[
            'multi' => false,   //该字段是否是可多选，
            'value' => true,  //值不放备注中无视
            'unit'=>true,//金额单位，true是元，false是分
            'key' => '所有单期投注最大限额'
        ],
        'per_bet_max'=>[
            'multi' => false,   //该字段是否是可多选，
            'value' => true,  //值不放备注中无视
            'unit'=>true,//金额单位，true是元，false是分
            'key' => '个人单期投注最大限额'
        ]
    ];

//    public function getTruenameAttribute($value)
//    {
//        return empty($value) ? 'unknow':$value;
//    }

}