<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 15:28
 */

namespace Model\Admin;


class ServiceAna extends \Illuminate\Database\Eloquent\Model
{
    public  $timestamps = false;

    protected $table = 'service_ana';

    protected $fillable = ['content','service_folder_id'];

}