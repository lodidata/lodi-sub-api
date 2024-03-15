<?php

use Logic\Admin\BaseController;

/*
 * 客服统计 今日统计
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计今日统计';
    const DESCRIPTION = '客服统计今日统计';
    
    const QUERY = [
        'page' => '页码',
        'page_size' => '每页大小',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'received_count' => 'int #今日接待会话',
            'receiving_count' => 'int #当前接待用户数',
            'wait_count' => 'int #当前等待用户数',
            'comment_result' => 'array #今日会话满意度',
            'name' => 'string #今日会话满意度名称',
            'count' => 'string #今日会话满意度数量',
            'online_customer_service_count' => '当前客服在线人数'

        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {

        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/customer_service/today_count';

        $data = $manage->getBaseStatistics($url);
        $result = [];
        if ($data['error'] == 0) {
            $result['received_count'] = $data['data']['received_count'];
            $result['receiving_count'] = $data['data']['receiving_count'];
            $result['wait_count'] = $data['data']['wait_count'];
            $result['online_customer_service_count'] = $data['data']['online_customer_service_count'];
            $result['comment_result'] = $data['data']['comment_result'];
            $attributes['total'] = 1;
            $attributes['number'] = 1;
            $attributes['size'] = 20;
            if (!$attributes['total'])
                return [];
            return $this->lang->set(0, [], $data, $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }

};