<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 17:11
 */

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '后台操作日志';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'created_uname' => 'string() #操作者名称',
        'user_name'     => 'string() #被操作者名字',
        'ip'            => 'string() #ip地址',
        'op_type'       => 'string() #操作类型，英文，add: 新增,delete: 删除,update: 修改, check: 审核,login: 登录,logout: 登出,',
        'module'        => 'string() #模块，参见接口：后台日志操作类型列表',
        'result'        => 'string() #成功 success，失败 fail',
        'date_from'     => 'datetime() #查询起始日期',
        'date_to'       => 'datetime() #查询失败日期',
        'page'          => 'int() #第几页',
        'page_size'     => 'int() #每页多少条'
    ];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
       'verifyToken','authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();

        $query = DB::connection('slave')->table('logs as l')->selectRaw('l.*,u.name as user_name')->leftJoin('user as u','l.user_id','=','u.id');
        $query = isset($params['created_uname']) && !empty($params['created_uname']) ? $query->where('l.created_uname',$params['created_uname']) : $query;
        $query = isset($params['user_name']) && !empty($params['user_name']) ? $query->where('u.name',$params['user_name']) : $query;
        $query = isset($params['ip']) && !empty($params['ip']) ? $query->where('l.ip',$params['ip']) : $query;
        $query = isset($params['op_type']) && !empty($params['op_type']) ? $query->where('l.op_type',$params['op_type']) : $query;
        $query = isset($params['result']) && !empty($params['result']) ? $query->where('l.result',$params['result']) : $query;
        $query = isset($params['date_from']) && !empty($params['date_from']) ? $query->where('l.created','>=',$params['date_from']) : $query;
        $query = isset($params['date_to']) && !empty($params['date_to']) ? $query->where('l.created','<=',$params['date_to']) : $query;
        $query = isset($params['module']) && !empty($params['module']) ? $query->where('l.module','=',$params['module']) : $query;
        $attributes['total']  = $query->count();
        $res = $query->orderBy('l.created','desc')->forPage($params['page'],$params['page_size'])->get()->toArray();

        if(!$res){
            return [];
        }
        $attributes['number'] = $params['page'];

        return $this->lang->set(0,[],$res,$attributes);

    }


};
