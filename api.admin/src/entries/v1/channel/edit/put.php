<?php
/**
 * 添加社区交流
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Log;
use Logic\Admin\BaseController;
use Model\Admin\CommunityBbs;
return new class() extends BaseController
{
    const TITLE = '编辑社区交流';
    const DESCRIPTION = '编辑社区交流';
    const PARAMS       = [
        'id' => 'int #社区ID',
        'name' => 'string #社区名称',
        'icon' => 'string #图标展示',
        'jump_url' => 'string #跳转链接',
        'status' => 'int #状态，0启用 1停用',
    ];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id)
    {
        $params = $this->request->getParams();
        //判断参数是否有误
        $params['id'] = $id > 0 ? $id : 0;
        if(empty($params['number'])){
            return $this->lang->set(886,['渠道编号不能为空']);
        }
        if($params['id'] > 0){
            $info = \DB::table('channel_management')->where('id', '!=', $params['id'])->where('number','=', $params['number'])->first();
        }else{
            $info = \DB::table('channel_management')->where('number','=', $params['number'])->first();
        }
        if(!empty($info)){
            return $this->lang->set(886,['渠道编号已存在']);
        }
        if(empty($params['name'])){
            return $this->lang->set(886,['渠道名称不能为空']);
        }
        if(empty($params['url'])){
            return $this->lang->set(886,['推广地址不能为空']);
        }
        $params['remark'] = $params['remark'] ?? '';
        $params['admin_user'] = $params['admin_user'] ?? '';
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '渠道管理', '渠道管理', '编辑渠道', 1, "社区ID：{$params['id']}");
        /*============================================================*/
        return $this->updateChannel($params);
    }

    //编辑渠道
    protected function updateChannel($params){
        $data = [
            'number'     => $params['number'],
            'name'       => $params['name'],
            'remark'     => $params['remark'],
            'url'        => $params['url'],
            'admin_user' => $params['admin_user'],
        ];
        try{
            if($params['id'] > 0){
                \DB::table('channel_management')->where('id', $params['id'])->update($data);
            }else{
                \DB::table('channel_management')->insert($data);
            }
        }catch (\Exception $e){
            return $this->lang->set(886,['编辑渠道失败']);
        }
        return $this->lang->set(0);
    }

};
