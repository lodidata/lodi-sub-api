<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class Notice extends LogicModel {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'notice';

    protected $title_param = 'title';

//    protected $hidden = ['lang'];
    protected $fillable = ['admin_uid','admin_name','send_type','type','recipient','title','content','lang','popup_type','pic','status','start_time','end_time','created','updated'];

    /**
     * @param \DateTime|int $value
     * @return false|int
     * @author dividez
     */
    public function fromDateTime($value){
        return strtotime(parent::fromDateTime($value));
    }

    /*
    *
    * @return int
    */
    public function freshTimestamp() {
        return time();
    }

    public static function getById($id){

        return self::find($id);

    }
    public function getSendTypeAttribute($value)
    {
        $arr = ['会员','代理','自定义'];
        return $arr[$value-1];
    }

    public function getPopupTypeAttribute($value)
    {
        $arr = ['登录弹出','首页弹出','滚动公告'];
        return $arr[$value-1];
    }

    public function getStartTimeAttribute($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    public function getEndTimeAttribute($value)
    {
        return date('Y-m-d H:i:s',$value);
    }


    public $desc_attributes=[
        'title'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => '标题'
        ],
        'popup_type'=>[
            'multi' => false,
            'value' => true,  //结果直接带上去 写在备注小（）内
            'key' => [
                1=>'弹出类型：登录弹出',
                2=>'弹出类型：首页弹出',
                3=>'弹出类型：滚动公告'
            ]
        ],'status'=>[
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [
                1=>'启用',
                0=>'停用'
            ]
        ]
    ];


}