<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 16:38
 */
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '系统日志--操作类型列表';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    public function run()
    {
        $UserOpTypes = [
            ["id" => 1, "name" => "登陆"],
            ["id" => 2, "name" => "取款申请"],
            ["id" => 3, "name" => "充值申请"],
            ["id" => 4, "name" => "申请活动奖励"],
            ["id" => 5, "name" => "修改登录密码"],
            ["id" => 6, "name" => "修改取款密码"],
            ["id" => 7, "name" => "修改个人信息"],
            ["id" => 8, "name" => "会员注册"],
            ["id" => 9, "name" => "代理注册"],
            ["id" => 10, "name" => "转账"],
            ["id" => 11, "name" => "修改银行卡信息"],
            ["id" => 12, "name" => "投注"]
        ];
        return $UserOpTypes;
    }
};
