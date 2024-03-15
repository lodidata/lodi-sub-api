<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '新增/修改会员标签';
    const DESCRIPTION = '会员标签--若不存在新增，存在则更新';
    
    const QUERY       = [];
    
    const STATEs      = [
//        \Las\Utils\ErrorCode::DATA_EXIST => '重复标签'
    ];
    const PARAMS      = [
        'name'      => 'string() #标签名称',
        'desc'      => 'string() #描述',
        'admin_uid' => 'int() #操作者uid，取登入时值，默认为0'
    ];
    const SCHEMAS     = [
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    /**
     * @param null $id 标签id，新增时为空
     * @return bool|mixed
     */
    public function run($id = null)
    {
        $admin_uid = $this->playLoad['uid'] ;

        $params = $this->request->getParams();

        (new BaseValidate([
            'id'  => 'require|integer',
            'desc'  => 'require|max:250',
        ]))->paramsCheck('',$this->request,$this->response);

        if($params['id'] == -1){
            //新增时
            (new BaseValidate([
                'name'=>'require|unique:label,title',
            ]))->paramsCheck('',$this->request,$this->response);

            $res = DB::table('label')->insert(['title'=>$params['name'],'content'=>$params['desc'],'admin_uid'=>$admin_uid]);

        }else{
            //更新时
            $data = DB::table('label')->find($id);
            if(!$data){
                return $this->lang->set(10015);
            }
            (new BaseValidate([
                'name'=>'require|unique:label,title,'.$id.',id',
            ]))->paramsCheck('',$this->request,$this->response);

            /*============================日志操作代码================================*/
            $info = DB::table('label')->where('id',$id)->find($id);
            /*============================================================*/

            $res = DB::table('label')->where('id',$id)->update(['title'=>$params['name'],'content'=>$params['desc'],'admin_uid'=>$admin_uid]);
            /*============================日志操作代码================================*/
            $str="";
            if($info->title!=$params['name']){
                $str.="[{$info->title}]更改为[{$params['name']}]";
            }
            if($info->content!=$params['desc']){
                $str.="[{$info->content}]更改为[{$params['desc']}]";
            }
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员标签', '会员标签', '编辑', $sta, $str);
            /*============================================================*/
        }

        if($res !== false){
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);

    }
};
