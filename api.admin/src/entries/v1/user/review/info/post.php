<?php

use Logic\Admin\BaseController;
use Logic\User\Review;

return new class() extends BaseController
{
    const TITLE       = '根据用户名获取审核资料';
    const DESCRIPTION = '根据用户名获取审核资料（真实姓名,登录密码,PIN密码,银行卡号）';

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
        if(empty($params['account'])) {
            return createRsponse($this->response, 200, -2, '请输入你要变更的会员账号');
        }

        $user = new Review($this->ci);

        $data = $user->queryReview($params);

        return $this->lang->set(0, [], $data);
    }
};