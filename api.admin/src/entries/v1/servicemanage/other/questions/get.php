<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 13:58
 */

use Logic\Admin\BaseController;

/*
 * 其他设置 问题类型列表
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 其他设置问题类型列表';
    const DESCRIPTION = '问题类型列表';

    const QUERY = [
        'page_size' => 'string #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'string #页码，默认1',
        'order_by' => ''
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'id' => 'int #数据id',
            'node_id' => 'string #公司ID',
            'question_type' => 'int #问题类型',
            'question_content' => 'int #问题类型内容',
            'question_status' => 'int #问题的状态，=0表示禁用，=1表示启用',
            'create_time' => 'int #添加时间，已经格式化',
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
        $url = $site['url'] . '/question?' . http_build_query($param);
        $data = $manage->getBaseStatistics($url, [], 'GET');
        if ($data['error'] == 0) {

            $attributes['question_is_open'] = $data['data']['question_is_open'];

            $attributes['total'] = $data['data']['total'];
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];

            return $this->lang->set(0, [], $data['data']['data'], $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};