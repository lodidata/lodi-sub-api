<?php
use Model\Admin\Role;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '依据权限获取菜单栏目';
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

    public function run()
    {
        $auths = explode(',',\DB::table('admin_user_role')->where('id',$this->playLoad['rid'])->value('auth'));
        $memu = \DB::table('admin_user_role_auth')->whereIn('id',$auths)->orderBy('pid','ASC')->get()->toArray();

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        $list = [];
        foreach ($memu as $key => $value){
            $value = (array)$value;
            if($value['pid'] == 0) {//
                $list[$value['id']] = $value;
            }else {
                $list[$value['pid']]['child'][] = $value;
            }
        }
        ksort($list);
        return array_values($list);
    }

};
