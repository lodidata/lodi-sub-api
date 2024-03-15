<?php
/**
 * 新增消息
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Log;
use Logic\Admin\Message as messageLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\MessageValidate;

return new class() extends BaseController {
    const TITLE       = '新增消息';
    const PARAMS      = [
        'send_type'        => 'int(required) #发送对象；1 会员等级，2 代理，3 自定义',
        'send_type_value' => 'string #发送类型的值，send_type=1，可能为：LV1,LV3；对于2，只一种值，1；对于3，可能为：xlz1,xlz2...',
        'title'            => 'string() #消息标题',
        'desc'             => 'string() #消息内容',
        'message_type'    => 'int() #消息类型，1重要消息 2一般消息',
        'admin_uid'        => 'int() #操作者uid'
    ];
    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        (new messageValidate())->paramsCheck('post',$this->request,$this->response);//参数校验

        $params = $this->request->getParams();
        $params['admin_uid'] = $this->playLoad['uid'];
        $params['admin_name'] = $this->playLoad['nick'];

        $res=(new messageLogic($this->ci))->createMessage($params);
        //日志操作代码
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '消息管理', '消息管理', "新增消息", $sta, "发送对象：{$params['send_type_value']}/标题:{$params['title']}");
        return $res;
    }
};
