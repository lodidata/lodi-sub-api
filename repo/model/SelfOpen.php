<?php

namespace Model;


class SelfOpen extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'self_open';

    public $timestamps = false;

    protected $fillable = [
    ];


    /**
     * @param $lotteryId
     * @param $lotteryNumber
     * @return bool
     */
    public static function getId($lotteryId, $lotteryNumber){
        $where = [
            'lottery_id'     => $lotteryId,
            'lottery_number' => $lotteryNumber
        ];

        return Self::where($where)->value('id');
    }

}