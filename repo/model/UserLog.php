<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 15:22
 */

namespace Model;

class UserLog extends \Illuminate\Database\Eloquent\Model {

    public $timestamps = false;

    protected $table = 'user_logs';

    protected $fillable = ['user_id', 'name','platform', 'log_type', 'log_value', 'log_ip', 'memo', 'domain', 'status', 'version'];

    public static function boot() {
        parent::boot();

        static::creating(function ($obj) {
           $obj->log_ip = \Utils\Client::getIp();
           $obj->domain = str_replace(['https', 'http'], '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        });
    }
}