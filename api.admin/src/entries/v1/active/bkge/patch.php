<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '返佣活动启用关闭';
    const DESCRIPTION = '';
    

    const QUERY       = [
    ];
    const PARAMS      = [

    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    
    public function run($id)
    {
        $status = $this->request->getParam('status');
        if(in_array($status,['disabled','enabled'])) {
            $model = \Model\Admin\ActiveBkge::find($id);
            if(!$model){
                $this->lang->set(10015);
            }
            $model->save(['status'=>$status]);
        }
        return $this->lang->set(0);
    }
};
