<?php
/**
 * 消息发布
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Log;
use Logic\Admin\Message as messageLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController {
    const TITLE       = '发布消息';
    
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
        $res=(new messageLogic($this->ci))->messagePublish($id);

        /*===日志操作代码===*/
        $message = DB::table('message')->find($id);
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '消息公告', '消息公告', "发布", $sta, "发送对象：{$message->recipient}/标题:{$message->title}");
        return $res;
    }
};
