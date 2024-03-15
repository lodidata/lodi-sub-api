<?php
/**
 * 订阅表
 */

namespace Model;


class Subscribe extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'subscribe';

    public $timestamps = false;

    protected $fillable = [
    ];


    /**
     * 判断手机号是否订阅 （true:已订阅，false:未订阅）
     * @param $mobile
     * @return bool
     */
    public static function isSubscribe($mobile){
        $mobile = \Utils\Utils::RSAEncrypt($mobile);
        $where = [
            'mobile' => $mobile
        ];

        return Self::where($where)->first() ? true : false;
    }

    /**
     * 插入手机号
     * @param $code
     * @param $mobile
     * @return mixed
     */
    public static function insertMobile($mobile){
        $mobile = \Utils\Utils::RSAEncrypt($mobile);
        $data  = [
            'mobile' => $mobile
        ];

        return self::insert($data);
    }
}