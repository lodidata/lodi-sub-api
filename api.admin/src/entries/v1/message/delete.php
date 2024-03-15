<?php
/**
 * 消息删除
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Message as messageLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController {
    const TITLE       = '删除指定消息';
    const PARAMS      = [
        'id' => 'int(required) #消息id',
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id)
    {
        $this->checkID($id);
        return (new messageLogic($this->ci))->delMessageById($id);
    }
};
