<?php

namespace Model\Admin;

class AdminUser extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'admin_user';

    public function getTruenameAttribute($value) {
        return empty($value) ? 'unknow' : $value;
    }

    public function getNickAttribute($value) {
        return empty($value) ? 'anonymity' : $value;
    }

    public static function getRecords($condition = [], $page = 1, $pageSize = 20) {
//        $where[] = " u.truename != '系统账号'";  //系统账号不显示
        $where = [];
        isset($condition['uid']) && $where[] = " u.id = '{$condition['uid']}'";
        isset($condition['status']) && $where[] = " u.status = '{$condition['status']}'";
        isset($condition['id']) && $where[] = " l.id = '{$condition['id']}'";
        isset($condition['username']) && $where[] = " u.username = '{$condition['username']}'";
        isset($condition['truename']) && $where[] = " u.truename LIKE '%{$condition['truename']}%'";

        $where = count($where) ? 'WHERE ' . implode(' AND', $where) : '';

        $sql = "
          SELECT u.id,u.username, u.truename, u.part, u.job , r.role, r.id AS role_id, u.loginip, u.logintime, u.`status`, 0 AS is_bind
          FROM admin_user u 
          LEFT JOIN admin_user_role_relation l ON l.uid = u.id 
          LEFT JOIN admin_user_role r ON r.id = l.rid 
            {$where} 
          order by u.id desc LIMIT {$pageSize} OFFSET " . ($page - 1) * $pageSize;

        $data = \DB::select($sql);

        $countSql = "select count(1) as cnt  FROM admin_user u 
          LEFT JOIN admin_user_role_relation l ON l.uid = u.id 
          LEFT JOIN admin_user_role r ON r.id = l.rid 
            {$where} ";

        $countData = \DB::select($countSql);
//print_r($data);exit;
        return ['data' => $data, 'count' => $countData];
    }
}