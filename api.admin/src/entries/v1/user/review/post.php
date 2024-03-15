<?php

use Logic\Admin\AdminAuth;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '资料审核';
    const DESCRIPTION = '修改用户基础信息审核（真实姓名,登录密码,PIN密码,银行卡号）';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'user_name'  => 'string(required) #用户',
    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];


    public function run()
    {
        $params = $this->request->getParams();
        $params['uid'] = $this->playLoad['uid'];

        $user = new \Logic\User\Review($this->ci);

        return $user->addReview($params);
    }


};