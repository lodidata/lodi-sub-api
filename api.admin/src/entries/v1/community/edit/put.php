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

    public function run()
    {
        $params = $this->request->getParams();
        //判断参数是否有误
        if(empty($params['name'])){
            return $this->lang->set(12001);
        }
        if(!in_array($params['status'], [0,1])){
            return $this->lang->set(10010);
        }
        if(!is_numeric($params['id']) || $params['id'] <= 0){
            return $this->lang->set(10010);
        }
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '社区交流', '管理社区', '编辑社区', 1, "社区ID：{$params['id']}");
        /*============================================================*/
        return $this->updateCommunity($params);
    }

    //新增社区
    protected function updateCommunity($params){
        $communityModel = new CommunityBbs();
        $community = $communityModel::find($params['id']);
        if(empty($community)){
            return $this->lang->set(10010);
        }
        $community->name = $params['name'];
        $community->icon = replaceImageUrl($params['icon']);
        $community->jump_url = $params['jump_url'];
        $community->status = $params['status'];
        try{
            $community->save();
        }catch (\Exception $e){
            return $this->lang->set(10404);
        }
        return $this->lang->set(0);
    }

};
