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
    const TITLE = '删除社区交流';
    const DESCRIPTION = '删除社区交流';
    const PARAMS       = [
        'id' => 'int #社区ID',
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
        if(!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0){
            return $this->lang->set(10010);
        }
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '社区交流', '管理社区', '删除社区', 1, "社区ID：{$params['id']}");
        /*============================================================*/
        return $this->delCommunity($params);
    }

    //新增社区
    protected function delCommunity($params){
        try{
            \DB::table('community_bbs')->where('id', '=', $params['id'])->delete();
        }catch (\Exception $e){
            return $this->lang->set(10404);
        }
        return $this->lang->set(0);
    }

};
