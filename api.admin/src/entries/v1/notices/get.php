<?php

use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '获取公告';
    const DESCRIPTION = '公告';
    
    const QUERY       = [
        'page'      => 'int #页数',
        'page_size' => 'int #每页显示条数',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'         => 'ID',
            'status'     => '状态（1：发布，0：未发布）',
            'popup_type' => '弹出类型(1：登录弹出，2：首页弹出，3：滚动公告）',
            'language_id' => '语言（1：简体中文，2：繁体中文，3：英文）',
            'type'       => '公告类型(1：彩票，2：视讯，3：体育)',
            'send_type'  => '发送类型（1：会员，2：代理，3：自定义）',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'       
    ];
    
    public function run()
    {
        $params = $this->request->getqueryParams();
        $params['language_id'] = isset($params['language_id']) ? intval($params['language_id']) : 0;
        return (new noticeLogic($this->ci))->getNoticeList($params);
        
        
    }
};
