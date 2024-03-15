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
    const TITLE = '添加社区交流';
    const DESCRIPTION = '添加社区交流';
    const PARAMS       = [
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
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '社区交流', '管理社区', '新增社区', 1, "社区名称：{$params['name']}");
        /*============================================================*/
        return $this->addCommunity($params);
    }

    //新增社区
    protected function addCommunity($params){
        $communityModel = new CommunityBbs();
        $communityModel->name = $params['name'];
        $communityModel->icon = replaceImageUrl($params['icon']);
        $communityModel->jump_url = $params['jump_url'];
        $communityModel->status = $params['status'];
        try{
            $communityModel->save();
        }catch (\Exception $e){
            return $this->lang->set(10404);
        }
        return $this->lang->set(0);
    }

};
