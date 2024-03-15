<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class Level extends \Illuminate\Database\Eloquent\Model {

//    protected $dateFormat = 'Y-m-d H:i:s';

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'level';

//    protected $hidden = ['lang'];

    /**
     * @param \DateTime|int $value
     * @return false|int
     * @author dividez
     */
//    public function fromDateTime($value){
//        return strtotime(parent::fromDateTime($value));
//    }
    /*
    *
    * @return int
    */
//    public function freshTimestamp() {
//        return time();
//    }

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