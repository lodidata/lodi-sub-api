<?php

use Logic\Admin\Log;
use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\NoticeValidate;

return new class() extends BaseController
{
    const TITLE = '新增公告';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        'send_type' => 'range(1..3)(required) #发送类型，给哪种人群；1 会员层级，2 代理，3 自定义',
        'recipient' => 'string() #发送类型的值，对于上述1，可能为：1,3...；对于2，只一种值，1；对于3，可能为：xlz1,xlz2...',
        'language_id' => 'int(required) #语言id，通过接口获取，',
        'type' => 'int(required) #公告类型',
        'title' => 'string() #公告标题',
        'content' => 'string() #公告内容',
        'start_time' => 'date() #开始时间',
        'end_time' => 'date() #结束时间',
        'popup_type' => 'int() #弹出类型，1 登录弹出，2 首页弹出，3 滚动公告',
    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::LOST_PARAM => '内容缺失'
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        (new noticeValidate())->paramsCheck('post', $this->request, $this->response);//参数校验

        $params = $this->request->getParams();
        $params['admin_uid'] = $this->playLoad['uid'];
        $params['admin_name'] = $this->playLoad['nick'];
        $params['language_id'] = isset($params['language_id']) ? intval($params['language_id']) : 1;
        $params['type'] = isset($params['type']) ? intval($params['type']) : 2;
        $params['sort'] = isset($params['sort']) ? intval($params['sort']) : 0; // 前端如为空传递过来为空字符串，需要兼容处理下

        /*============================日志操作代码================================*/
        $popup_type = $params['popup_type'] == 1 ? "登录弹出" : ($params['popup_type'] == 2 ? "首页弹出" : "滚动公告");
        $str = '';

        $res = (new noticeLogic($this->ci))->createNotice($params);
        $sta = $res !== false ? 1 : 0;

        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '公告管理', '新增公告', $sta, "弹出类型：{$popup_type}/标题：{$params['title']}$str");
        /*============================================================*/
        return $res;

    }
};
