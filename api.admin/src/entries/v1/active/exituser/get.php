<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

/**
 * 检测用户（检测用户是否存在）
 */
return new class() extends BaseController
{
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'       
    ];
    
    public function run()
    {

        $userName = $this->request->getParam('userName');
        $data=DB::table('user')
            ->select('id','name')
            ->where('name','=',$userName)
            ->get()
            ->first();
        return (array)$data;
        
    }
};
