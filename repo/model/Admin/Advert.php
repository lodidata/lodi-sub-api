<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class Advert extends LogicModel {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'advert';

    public $title_param = 'name';          //  新增和删除时 需要说明是删除新增哪个，对应表中的某例


    public static function getById($id){

        return self::find($id);

    }

    public $desc_attributes=[
        'name'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '广告名称'
        ],
        'pf'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => [
                'pc'=>'PC端',
                'h5'=>'移动端'
            ]
        ],'status'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [
                'enabled'=>'启用',
                'disabled'=>'停用'
            ]
        ],'position'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => [
                'home'=>'显示位置：首页',
                'egame'=>'显示位置：电子页',
                'live'=>'显示位置：视讯页',
                'lottery'=>'显示位置：彩票页',
                'agent'=>'显示位置：代理页',
                'sport'=>'显示位置：体育页'
            ]
        ],'link_type'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [
                1=>'外部链接',
                2=>'站内活动',
                3=>'跳转模块',
                4=>'指定游戏',
            ]
        ],'link'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => '链接'
        ],'picture'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => '图片'
        ]
    ];


//    public function getTruenameAttribute($value)
//    {
//        return empty($value) ? 'unknow':$value;
//    }

}