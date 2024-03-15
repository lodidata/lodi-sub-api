<?php

use lib\validate\admin\NoticeValidate;
use Logic\Admin\Log;
use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '公告信息编辑';
    const DESCRIPTION = '';
    
    const QUERY = [
        'id' => 'int(required) #id',
    ];
    
    const PARAMS = [
        'send_type' => 'range(1..3)(required) #发送类型，给哪种人群；1 会员层级，2 代理，3 自定义',
        'recipient' => 'string() #发送类型的值，对于上述1，可能为：1,3...；对于2，只一种值，1；对于3，可能为：xlz1,xlz2...',
        'language_id' => 'int(required) #语言id，通过接口获取，',
        'title' => 'string() #公告标题',
        'content' => 'string() #公告内容',
        'start_time' => 'date() #开始时间',
        'end_time' => 'date() #结束时间',
        'popup_type' => 'int() #弹出类型，1 登录弹出，2 首页弹出，3 滚动公告',
    ];
    const SCHEMAS = [];

    public $id;

    public function run($id = null)
    {
        $this->checkID($id);
       (new noticeValidate())->paramsCheck('', $this->request, $this->response);//参数校验
        $params = $this->request->getParams();

        unset($params['language_id']);
        unset($params['username']);
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time'].'23:59:59');
        $notice = \Model\Admin\Notice::find($id);

        $notice->id = $id;
        $notice->admin_uid = $this->playLoad['uid'];
        $notice->admin_name = $this->playLoad['nick'];
        foreach ($params as $key=>$param) {
            if($key == 'imgs'){
                $param = replaceImageUrl($param);
            }
            $notice->$key=$param;
        }
        $res=$notice->save();
        if($res!==false){
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
