<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model;

class GameOrderError extends \Illuminate\Database\Eloquent\Model {
    protected $table      = 'game_order_error';
    //protected $connection = 'slave';
    public $timestamps = false;
}