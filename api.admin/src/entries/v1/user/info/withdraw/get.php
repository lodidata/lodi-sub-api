<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '用户详情:取款稽查信息';
    const DESCRIPTION = 'map # channel: registe=网站注册, partner=第三方,reserved =保留';
    
    const QUERY       = [
        'id'        => 'int(required) #用户id',
        'type'      => 'enum[stat,base,balance,withdraw,bank](required) #获取细分项，可能值：统计 stat，基本信息 base，账户余额 balance，取款稽核 withdraw，银行信息 bank',
        'page'      => 'int()#当前页数',
        'page_size' => 'int() #一页多少条数'
    ];
    
    const STATEs      = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS      = [];
    const SCHEMAS     = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        $dml = new \Logic\Wallet\Dml($this->ci);
        $tmp = $dml->getUserDmlData($id);
        $dmlData = [
            'factCode' => $tmp->total_bet,
            'codes'    => $tmp->total_require_bet,
            'canMoney' => $tmp->free_money,
            'balance'  => \Model\User::getUserTotalMoney($id)['lottery'] ?? 0 ,
        ];
        return $dmlData;

    }



};
