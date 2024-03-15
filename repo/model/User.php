<?php

namespace Model;


class User extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'user';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'currency',
        'wallet_id',
        'agent_id',
        'name',
        'email',
        'telphone_code',
        'mobile',
        'salt',
        'password',
        'ranting',
        'strong',
        'online',
        'role',
        'tags',
        'ip',
        'first_account',
        'language',
        'invit_code',
        'channel_id',
        'channel',
        'login_ip',
        'last_login',
        'auth_status',
        'limit_status',
        'limit_video',
        'limit_lottery',
        'state',
        'lock',
        'game_type',
        'tp_password_initial',
        'withdraw_error_times',
        'origin',
        'origin_memo',
        'agent_switch',
        'first_recharge_time',
        'wechat',
        'isVertical',
        'is_verify'
        //'is_test',
    ];

    public static function boot() {

        parent::boot();

        static::creating(function ($obj) {
            $ip = \Utils\Client::getIp();
            $obj->ip = \DB::raw("inet6_aton('$ip')");
            $obj->salt = self::getGenerateChar(6);
            $obj->password = self::getPasword($obj->password, $obj->salt);
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
        return UserData::where('user_id', $userId)
                   ->value('free_money');
    }

    //获取总余额
    public static function getUserTotalMoney($userId) {
        $user = User::where('id', $userId)
                    ->select(['wallet_id'])
                    ->first();

        $funds = \DB::table('funds')
                    ->select(['balance'])
                    ->where('id', $user['wallet_id'])
                    ->first();

        return [
            'lottery' => $funds ? $funds->balance : 0,

            'third' => 0,
        ];
    }

    public static function getBalance($userId) {
        $user = User::where('id', $userId)
            ->select(['wallet_id'])
            ->first();

        $funds = \DB::table('funds')
            ->select(['balance'])
            ->where('id', $user['wallet_id'])
            ->first();

        return $funds->balance;
    }

    /**
     * 获取账号
     * @param $userId
     * @return mixed
     */
    public static function getAccount($userId){
        $account = self::where('id', $userId)
                            ->value('name');
        return $account;
    }

    /**
     * 判断账号是否存在 (true:存在,false:不存在)
     * @param $name
     * @return bool
     */
    public static function getAccountExist($name){
        $id = self::where('name', $name)
            ->select('id')->first();

        return $id ? true : false;
    }


    //更新可提余额
    public static function updateFreeMoney($userId, $amount) {
        return UserData::where('user_id', $userId)
                   ->update(['free_money' => $amount]);
    }

    //新版，更新可提余额，应有打码量，实际打码量等
    public static function updateBetData(int $userId, array $data) {
        $update = [];

        foreach ($data as $k => $v) {
            if (in_array($k, ['total_bet', 'total_require_bet', 'free_money']))
                $update[$k] = $v;
        }

        if ($update) {
            return \DB::table('user_data')
                      ->where('user_id', $userId)
                      ->update($update);
        } else {
            return false;
        }
    }

    /**
     * @param      $userName
     * @param bool $exact
     *
     * @return array
     */
    public static function getAgentIdByName($userName, $exact = false) {
        return $exact ? self::whereRaw("locate('{$userName}',`name`) >0")
                            ->pluck('id')
                            ->toArray() : self::where('name', $userName)
                                              ->pluck('id')
                                              ->toArray();

    }

    /**
     * 1 : 接受优惠, 0 : 拒绝优惠
     * @param $userId
     * @return int
     */
    public static function needDepositCoupon($userId){
        $res = self::where('id',$userId)->whereRaw("find_in_set('refuse_sale',auth_status)")->get()->toArray();

        return $res ? 0 : 1;
    }

    /**
     * 更新 充值优惠属性
     * @param $userId
     * @param $value
     * @return int
     */
    public static function updateDepositCoupon($userId,$value){
        $res = \DB::table('user')->where('id',$userId)->update(['auth_status' => \DB::raw($value)]);
        return $res ? 1 : 0;
    }

    public static function getUserNum(){
        return self::count();
    }
    /**
     * 根据用户账号获取用户ID (返回用户ID)
     * @param $name
     * @return bool
     */
    public static function getAccountById($name){
         $info = self::where('name', $name)->select('id')->first();
        return $info->id ?? false;
    }
}

