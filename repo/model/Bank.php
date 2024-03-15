<?php

namespace Model;

class Bank extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'bank';

    public $timestamps = false;
    
    protected $fillable = [  
                        'id',
                        'code',
                        'code_num',
                        'sort',
                        'status',
                        'name',
                        'shortname',
                        'url',
                        'h5_logo',
                        'logo',
                        'created_uid',
                        'updated_uid',
                        'type',
                        ];

    public static function boot() {

        parent::boot();

    }


    public static function getRecords($params = '', $condition, $page = 1, $pageSize = 30) {
        $select = [
                'id',
                'code',
                'code_num',
                'sort',
                'status',
                'name',
                'shortname',
                'url',
                'logo',
                'created',
                'updated',
        ];

        if ($params == '*') {
            $select[] = \DB::raw("(select username from admin_user where admin_user.id = bank.created_uid) as created_uname");
            $select[] = 'created_uid';
            $select[] = \DB::raw("(select username from admin_user where admin_user.id = bank.updated_uid) as updated_uname");
            $select[] = 'updated_uid';
        }
        $table = Bank::select($select);

        if (isset($condition['status'])) {
            $table = $table->whereRaw($condition['status'] == 'enabled' ? "FIND_IN_SET('enabled', status)" : "!FIND_IN_SET('enabled', status)");
        }

        isset($condition['type']) && $table = $table->where('type', $condition['type']);
        isset($condition['name']) && $table = $table->whereLike('name', $condition['name'].'%');
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }   
}

