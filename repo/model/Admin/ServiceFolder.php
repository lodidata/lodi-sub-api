<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 15:28
 */

namespace Model\Admin;


class ServiceFolder extends \Illuminate\Database\Eloquent\Model
{
    public  $timestamps = false;

    protected $table = 'service_folder';

    protected $fillable = ['name','uid'];


    /*
     *
     * 获取常用语
     *
     * */
    public function ana()
    {
        return $this->hasMany('\Model\Admin\ServiceAna','service_folder_id','id');
    }

}