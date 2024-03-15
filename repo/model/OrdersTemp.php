<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model;

class OrdersTemp extends \Illuminate\Database\Eloquent\Model {
    protected $table      = 'orders_temp';
    //protected $connection = 'slave';
    public $timestamps = false;
}