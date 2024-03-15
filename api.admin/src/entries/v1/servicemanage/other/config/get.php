<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/15
 * Time: 16:00
 */


use Logic\Admin\BaseController;

/*
 * 其他设置获取设置数据
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 其他设置获取设置数据';
    const DESCRIPTION = '获取设置数据';

    const QUERY = [
        'page_size' => 'int #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'int #页码，默认1',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'service_reply_tip' => 'int #客服长时间未回复，系统自动提示: =0表示关闭；=1表示开启',
            'service_reply_duration' => 'int #客服长时间未回复，发送时长 秒（需要显示分，自行转换）',
            'service_reply_text' => 'string #客服长时间未回复，系统提示语',
            'user_reply_tip' => 'int #用户长时间未回复，系统自动提示: =0表示关闭；=1表示开启',
            'user_reply_duration' => 'int #用户长时间未回复，发送时长 秒（需要显示分，自行转换）',
            'user_reply_text' => 'string #用户长时间未回复，系统提示语',
            'auto_finish_duration' => 'int #长时间未回复自动结束会话，时长，秒（需要显示分，自行转换）'
        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {

        $param = $this->request->getParams();
        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/node_config/index';
        $data = $manage->getBaseStatistics($url, []);
        if ($data['error'] == 0) {

            $attributes['total'] = 1;
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];

            return $this->lang->set(0, [], $data['data'], $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};