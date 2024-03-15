<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 15:22
 */

namespace Model;

class UserChannelLogs extends \Illuminate\Database\Eloquent\Model {

    public $timestamps = false;

    protected $table = 'user_channel_logs';

    protected $fillable = ['user_id', 'channel_id','platform',  'status'];

    public static function boot() {
        parent::boot();

        static::creating(function ($obj) {
           $obj->log_ip = \Utils\Client::getIp();
           $obj->domain = str_replace(['https', 'http'], '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        });
    }
}