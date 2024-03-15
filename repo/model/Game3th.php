<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;

use Model\Admin\LogicModel;

class Game3th extends LogicModel {
    public $timestamps = false;
    protected $table = 'game_3th';

    public $orderCallbackList = [
        //'Logic\Game\Test' => 'testfunction',
        'Logic\GameApi\Common' => 'transferOrder'
    ];

    public static function getGameId($id){
        return self::where('id', $id)->value('game_id');
    }

}