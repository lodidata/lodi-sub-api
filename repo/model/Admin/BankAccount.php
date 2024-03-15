<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class BankAccount extends \Illuminate\Database\Eloquent\Model {

//    protected $dateFormat = 'Y-m-d H:i:s';

//    public  $timestamps = false;

    protected $table = 'bank_account';

//    protected $hidden = ['lang'];
    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    public static function getById($id){

        return self::find($id);

    }


    public function getTypeAttribute($value)
    {
        $typeArr=['1'=>'网银','2'=>'支付宝','3'=>'微信','4'=>'QQ支付','5'=>'京东支付'];

        return isset($typeArr[$value]) ?  $typeArr[$value] : $value;
    }
//
//    public function getPopupTypeAttribute($value)
//    {
//        $arr = ['登录弹出','首页弹出','滚动公告'];
//        return $arr[$value-1];
//    }


}