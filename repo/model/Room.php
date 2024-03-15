<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;

class Room extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'room';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'lottery_id',
        'hall_id',
        'room_name',
        'room_level',
        'number'

    ];

    public static $privateKey = 'fc5f5c21d85fe55325508a333223d51e';

    public static function getById($id)
    {

        return self::find($id);

    }
}