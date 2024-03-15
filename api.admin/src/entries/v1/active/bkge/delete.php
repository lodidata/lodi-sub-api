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
        $model = \Model\Admin\ActiveBkge::find($id);
        if(!$model){
            $this->lang->set(10015);
        }
        $re = $model->delete();
        return $re ? $this->lang->set(0) :  $this->lang->set(-1);
    }
};
