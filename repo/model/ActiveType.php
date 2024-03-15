<?php
namespace Model;
use DB;
use Model\Admin\LogicModel;
use Utils\Client;
use Utils\Utils;

class ActiveType extends LogicModel {

    protected $table = 'active_type';

    public $timestamps = false;

    protected $fillable = [
                          'id',
                          'name',
                          'description',
                          'sort',
                          'status',
                          'created_uid',
                          'updated_uid',
                        ];

    public static function boot() {
        parent::boot();
    }

    /**
     * 根据id修改
     * @param $params
     * @param $id
     * @return int
     */
    public static function updateByid($status,$id){
        $res=DB::table('active_type')
            ->where('id','=',$id)
            ->update(['status'=>$status]);
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 批量修改
     * @param $params
     * @return bool
     */
    public static function updateByAll($params){
        $res=Utils::updateBatch($params,'active_type');
        return $res;
    }
}

