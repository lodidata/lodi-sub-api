<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class Active extends LogicModel {

//    const UPDATED_AT = 'updated';
//
//    const CREATED_AT = 'created';

    protected $table = 'active';
    protected $title_param = 'title';
    public $timestamps = false;

//    protected $hidden = ['lang'];


    public static function getById($id){

        return self::find($id);

    }
//    public function getSendTypeAttribute($value)
//    {
//        $arr = ['会员','代理','自定义'];
//        return $arr[$value-1];
//    }
//
//    public function getPopupTypeAttribute($value)
//    {
//        $arr = ['登录弹出','首页弹出','滚动公告'];
//        return $arr[$value-1];
//    }


}