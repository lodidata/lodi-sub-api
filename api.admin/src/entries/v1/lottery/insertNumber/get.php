<?php

use Logic\Admin\BaseController;
use Model\LotteryInsertNumber;


return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '';
    const DESCRIPTION = '查看第一名和第16名插入数字';


    const QUERY       = [

    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        (new \lib\validate\BaseValidate([
            'lottery_id'      => 'require',
            'lottery_number'  => 'require',
        ]))->paramsCheck('',$this->request,$this->response);

        $req     = $this->request->getParams();
        $res     = LotteryInsertNumber::getNumber($req['lottery_id'], $req['lottery_number']);

        return $res;

    }

};
