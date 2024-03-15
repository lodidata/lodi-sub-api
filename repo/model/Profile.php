<?php

namespace Model;

class Profile extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'profile';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'region_id',
        'age',
        'avatar',
        'address',
        'nickname',
        'name',
        'gender',
        'birth',
        'email',
        'mobile',
        'qq',
        'weixin',
        'skype',
        'nationality',
        'birth_place',
        'idcard',
        'postcode',
        'comment',

    ];

    /**
     * 获取身份证号码
     */
    public static function getIdCard($number)
    {
        if (empty($number)) {
            return $number;
        }
        $number = \Utils\Utils::RSADecrypt($number);
        $number = '****' . substr($number, -4);
        return $number;
    }
    /**
     * 获取身份证号码
     */
    public static function getBankCard($number)
    {
        if (empty($number)) {
            return $number;
        }
        $number = \Utils\Utils::RSADecrypt($number);
        $number = substr($number, 0, 4) . '****' . substr($number, -4);
        return $number;
    }

    /**
     * 手机号码
     */
    public static function getMobile($mobile)
    {
        if (empty($mobile)) {
            return $mobile;
        }
        $mobile = \Utils\Utils::RSADecrypt($mobile);
        $mobile = substr($mobile, 0, 3) . '****' . substr($mobile, -4);
        return $mobile;
    }

    /**
     * 邮箱
     */
    public static function getEmail($email)
    {
        if (empty($email)) {
            return $email;
        }
        $email = \Utils\Utils::RSADecrypt($email);
        $email = substr($email, 0, 2) . '****' . substr($email, strpos($email, '@') - 2);
        return $email;
    }

    /**
     * qq
     */
    public static function getQQ($qq)
    {
        if (empty($qq)) {
            return $qq;
        }
        $qq = \Utils\Utils::RSADecrypt($qq);
        return $qq;
    }

    /**
     * qq
     */
    public static function getWechat($wechat)
    {
        if (empty($wechat)) {
            return $wechat;
        }
        $wechat = \Utils\Utils::RSADecrypt($wechat);
        return $wechat;
    }

    /**
     * qq
     */
    public static function getSkype($skype)
    {
        if (empty($skype)) {
            return $skype;
        }
        $skype = \Utils\Utils::RSADecrypt($skype);
        return $skype;
    }
}




