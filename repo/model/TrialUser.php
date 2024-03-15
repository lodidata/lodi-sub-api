<?php

namespace Model;


class TrialUser extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'trial_user';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'wallet_id',
        'name',
        'ip',
        'origin',
        'origin_memo',
    ];

    public static function boot() {
        parent::boot();

        static::creating(function ($obj){
            $ip = \Utils\Client::getIp();
            $obj->ip = \DB::raw("inet6_aton('$ip')");
        });
    }

    /**
     *
     * @param
     *            生成随机字符串
     *
     * @return string|unknown
     */
    public static function getGenerateChar($length = 6, $chars = null) {
        // 密码字符集，可任意添加你需要的字符
        $chars = $chars ?? "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:?|";
        $password = "";
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $password;
    }

    public static function getPasword($password, $salt) {
        return md5(md5($password) . $salt);
    }

    //获取可提余额
    public static function getFreeMoney($userId) {
        return TrialUser::where('id', $userId)
            ->select(['last_free_money'])
            ->first()->last_free_money;
    }

    //获取总余额
    public static function getUserTotalMoney($userId) {
        $wallet_id = TrialUser::where('id', $userId)->value('wallet_id');
        return [
            'lottery' => \DB::table('trial_funds')
                ->where('id', $wallet_id)
                ->value('balance'),
            'third'   => 0,
        ];
    }

    //更新可提余额
    public static function updateFreeMoney($userId, $amount) {
        return TrialUser::where('id', $userId)
            ->update(['last_free_money' => $amount]);
    }

}

