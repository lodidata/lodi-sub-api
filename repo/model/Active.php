<?php
namespace Model;
use DB;
use Model\Admin\LogicModel;

class Active extends LogicModel {

    protected $table = 'active';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'name',
                          'title',
                          'type_id',
                          'begin_time',
                          'end_time',
                          'link',
                          'open_mode',
                          'description',
                          'cover',
                          'language_id',
                          'language_name',
                          'content',
                          'content2',
                          'status',
                          'state',
                          'sort',
                          'created_uid',
                          'updated_uid',
                          'created_user',
                          'vender_type',
                        ];

    public static function boot() {
        parent::boot();

    }

}


