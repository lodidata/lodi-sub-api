<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/27
 * Time: 16:51
 */

namespace Model\Admin;


class ServiceUserSocket extends \Illuminate\Database\Eloquent\Model
{
    public  $timestamps = false;

    protected $table = 'service_user_socket';

    protected $fillable = ['original_user_id','plus_user_id','encrypt_user_id','user_type'];

}