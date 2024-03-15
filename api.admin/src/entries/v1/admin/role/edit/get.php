<?php
use Model\Admin\Role;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '编辑角色';
    const DESCRIPTION = '';

    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null)
    {

        $memu = \DB::table('admin_user_role_auth')->orderBy('sort','ASC')->where('status',1)->get([
            'id',
            'pid',
            'name AS title',
        ])->toArray();
        $auth_role = $id ? (array)\DB::table('admin_user_role')->where('id',$id)->first(['auth','member_control','list_auth']) : ['auth'=>'','member_control'=>'','list_auth'=>''];
        $memberControl =array("true_name"=>false, "bank_card"=> false,"address_book"=>false,"user_search_switch"=>false,"kefu_phone"=>false);

        $memu = \Utils\PHPTree::makeTree($memu,[],explode(',',$auth_role['auth']));
        $tmp = json_decode($auth_role['member_control'],true) ?? [];
        $memberControl = array_merge($memberControl,$tmp);
        return ['auth'=>$memu,'user'=>$memberControl,'user_search_switch'=>$memberControl['user_search_switch'] ?? false,'list_auth'=>$auth_role['list_auth'],];


    }

};
