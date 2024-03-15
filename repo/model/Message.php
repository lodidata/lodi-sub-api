<?php
namespace Model;
class Message extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'message';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'admin_uid',
                          'admin_name',
                          'send_type',
                          'recipient',
                          'title',
                          'content',
                          'type',
                          'status',
                          'user_id',
                          'active_type',
                          'active_id',
                          'created',
                          'updated',
                        ];

    public static function boot() {

        parent::boot();

    }
}

