<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/15
 * Time: 16:06
 */


use Logic\Admin\BaseController;

/*
 * 其他设置修改设置数据
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 其他设置修改设置数据';
    const DESCRIPTION = '修改设置数据';

    const QUERY = [

    ];
    
    const PARAMS = [
        'service_reply_tip' => 'int #客服长时间未回复，系统自动提示: =0表示关闭；=1表示开启',
        'service_reply_duration' => 'int #客服长时间未回复，发送时长 秒（需要显示分，自行转换）',
        'service_reply_text' => 'string #客服长时间未回复，系统提示语',
        'user_reply_tip' => 'int #用户长时间未回复，系统自动提示: =0表示关闭；=1表示开启',
        'user_reply_duration' => 'int #用户长时间未回复，发送时长 秒（需要显示分，自行转换）',
        'user_reply_text' => 'string #用户长时间未回复，系统提示语',
        'auto_finish_duration' => 'int #长时间未回复自动结束会话，时长，秒（需要显示分，自行转换）'
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {

        $param = $this->request->getParams();
        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/node_config/save';
        $data = $manage->getBaseStatistics($url, $param);
        if ($data['error'] == 0) {
            return $this->lang->set(0);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};
