<?php
namespace Model;

class ActiveSignUp extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'active_sign_up';

    public $timestamps = false;

    public static function boot() {
        parent::boot();
    }

    /**
     * 获取用户是否参与这个活动
     * @param $userId
     * @param $activeId
     * @return mixed
     */
    public static function getStatus($userId, $activeId){
        $where = [
            'user_id'   => $userId,
            'active_id' => $activeId,
        ];
        return self::where($where)->value('status');
    }

    /**
     * @param $userId
     * @param $activeId
     * @return mixed
     */
    public static function getInfo($userId, $activeId){
        $where = [
            'user_id'   => $userId,
            'active_id' => $activeId,
        ];
        $res = self::where($where)->first();
        return $res ? $res->toArray() : [];
    }

    /**
     * 参与活动
     * @param $userId
     * @param $activeId
     * @return mixed
     */
    public static function joinActive($userId, $activeId){
        $data = [
          'user_id'           => $userId,
          'active_id'         => $activeId,
          'apply_time'        => date('Y-m-d H:i:s'),
          'apply_status'      => 1,
          'times'             => 0,
        ];
        return self::insert($data);
    }

    /**
     * 更新信息
     * @param $id
     * @param $data
     */
    public static function updateInfo($id, $data){
        return self::where('id', $id)->update($data);
    }

    /**
     * 每次获取优惠 就更新次数和时间
     * @param $id
     * @param $times
     * @param $depositTime
     * @return bool
     */
    public static function updateTimesAndDepositTime($id, $times, $depositTime){
        if($times == 0){
            $whereRaw = 'first_deposit_time is null';
            $data['first_deposit_time'] = $depositTime;
        }elseif($times == 1){
            $whereRaw = 'second_deposit_time is null';
            $data['second_deposit_time'] = $depositTime;
        }elseif($times == 2){
            $whereRaw = 'third_deposit_time is null';
            $data['third_deposit_time'] = $depositTime;
        }else{
            return false;
        }

        $data['times']              =  $times + 1;
        //领完优惠 就只能玩电子游戏
        $data['can_play_all_game']  = 0;

        return self::where('id', $id)->whereRaw($whereRaw)->update($data);
    }

}
