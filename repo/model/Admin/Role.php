<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
use DB;

class Role extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'admin_user_role';

    public static function getRecords($params=[], $condition=[], $page = 1, $pageSize = 20) {
        $table = DB::table('admin_user_role');
        isset($condition['id']) && $table = $table->where('admin_user_role.id' , $condition['id']);
        isset($condition['role']) && $table = $table->where('admin_user_role.role' , $condition['role']);
        $select = [
            'id' => 'admin_user_role.id',
            'role' => 'admin_user_role.role',
            'num' => 'admin_user_role.num',
            'auth' => 'admin_user_role.auth',
            'member_control' => 'admin_user_role.member_control',
            'addtime' => 'admin_user_role.addtime',
            'username' => 'admin_user.username',

        ];
        $select = DB::mergeColumns($select, $params);
        $selectStr = join(',', array_values($select));
        if ($params == '*' || strpos($selectStr, '.username') !== false) {
            $table = $table->leftjoin('admin_user', 'admin_user.id', '=', 'admin_user_role.operator');
        }

         $data = $table->select(array_values($select))->orderBy('id','desc')->paginate($pageSize, ['*'], 'page', $page);
          return $data;
    }


}


