<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/9/17
 * Time: 16:24
 */

use Logic\Admin\BaseController;


return new class() extends BaseController
{
    const TITLE = '移代理动记录';
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run(){

        $params = $this->request->getParams();

        $query = DB::table('agent_move_log as aml')
                    ->leftJoin('user as u','aml.user_id','=','u.id')
                    ->leftJoin('admin_user as au','au.id','=','aml.admin_id')
                    ->selectRaw('aml.id,u.name as user_name,aml.old_agent,new_agent,au.username as admin_user,aml.created');

        $query = isset($params['user_name']) && !empty($params['user_name']) ? $query->where('u.name',$params['user_name']) : $query;
        $query = isset($params['admin_user']) && !empty($params['admin_user']) ? $query->where('au.username',$params['admin_user']) : $query;
        $query = isset($params['creaetd_from']) && !empty($params['creaetd_from']) ? $query->where('aml.created', '>=', $params['creaetd_from']) : $query;
        $query = isset($params['created_to']) && !empty($params['created_to']) ? $query->where('aml.created', '<=',$params['created_to']) : $query ;

        $total = $query->count();
        $attributes['total'] = $total;
        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];
        $result = $query->forPage($params['page'],$params['page_size'])->orderByDesc('aml.id')->get()->toArray();

        return $this->lang->set(0, [], $result, $attributes);

    }


};