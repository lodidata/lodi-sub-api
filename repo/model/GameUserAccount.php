<?php

namespace Model;

class GameUserAccount extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'game_user_account';

    public $timestamps = false;
    
    protected $fillable = [  
                        'id',
                        'user_id',
                        'user_name',
                        'user_account',
                        'user_password',
                        'game_type',
                        'created'
                        ];

    public static function getUserAccount($userId) {
        $select = [
                'user_account',
        ];

        $res = self::select($select)->where(['user_id' => $userId])->first();
        return $res ? $res->user_account : null;
    }
}

