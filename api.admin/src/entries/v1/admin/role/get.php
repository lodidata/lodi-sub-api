<?php
use Model\Admin\Role;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '角色列表';
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

        $params = $this->request->getParams();
        $table = DB::table('admin_user_role')
            ->leftjoin('admin_user', 'admin_user.id', '=', 'admin_user_role.operator')
//            ->whereNotIn('admin_user_role.id',[888888,999999])
            ->selectRaw('admin_user_role.id,
            admin_user_role.role,
            admin_user_role.num,
            admin_user_role.auth,
            admin_user_role.member_control,
            admin_user_role.addtime,
            admin_user.username');

        isset($params['id']) && $table = $table->where('admin_user_role.id' , $params['id']);
        isset($params['role']) && $table = $table->where('admin_user_role.role' , $params['role']);
        $attr['total'] = $table->count();
        $params['page_size'] = $params['page_size'] == 30 ? 9999 : $params['page_size'];
        $data = $table->orderBy('id','desc')->forPage($params['page'],$params['page_size'])->get()->toArray();
        foreach ($data as $val){
            if ($val->id == 888888 ){
                $val->role = '系统管理员';
            }elseif ($val->id == 999999 ){
                $val->role = '系统查询员';
            }
            $res[] = [
                'id' => $val->id,
                'name' => $val->role,
                'status' => 1,
            ];
        }
        $attr['num'] = $params['page'];
        $attr['size'] = $params['page_size'];

        return $this->lang->set(0,'',$data,$attr);

    }

};
