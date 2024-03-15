<?php
/**
 * 消息管理列表
 * @author Taylor 2019-01-11
 */
use Logic\Admin\Message as messageLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '消息列表';
    
    const PARAMS      = [
        'id'        => 'int #指定消息id',
        'title'     => 'string() 消息标题',
        'type'      => 'int #1 重要消息 2 一般消息',
        'admin_name'=> 'string # 发布人',
        'start_time'   => 'date()  #开始时间',
        'end_time'     => 'date()  #结束时间',
        'page'      => 'int() #default 1',
        'page_size' => 'int() #default 20'
    ];
    const SCHEMAS     = [
        [
            'id' => 'string #消息id',
            'admin_uid' => 'int #发送人id',
            'admin_name' => 'string #发送人',
            'send_type' => 'int #发送类型（1：会员层级，2：代理，3：自定义）',
            'recipient' => 'string #发送类型的值，send_type=1，可能为：LV1,LV3；对于2，只一种值，1；对于3，可能为：xlz1,xlz2...',
            'title' => 'string #消息标题',
            'content' => 'string #消息内容',
            'type' => 'int #1 重要消息 2 一般消息',
            'status' => 'int #发送状态 0未发布，1，已发布',
            'user_id' => 'int #用户id',
            'created' => 'string #创建时间',
            'updated' => 'string #更新时间',
            'username' => 'string #用户名',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];
    
    public function run()
    {
        $params = $this->request->getqueryParams();
        return (new messageLogic($this->ci))->getMesList($params, $params['page'],$params['page_size']);
    }
};
