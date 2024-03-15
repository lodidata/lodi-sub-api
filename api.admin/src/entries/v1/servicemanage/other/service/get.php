<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/8
 * Time: 14:31
 */


use Logic\Admin\BaseController;

/*
 * 客服统计 客服管理列表
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计客服管理列表';
    const DESCRIPTION = '客服统计客服管理列表';
    
    const QUERY = [
        'page_size' => 'int #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'int #页码，默认1',
        'order_by' => ''
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'uin' => 'int #客服id',
            'find' => 'string #客服账号',
            'nick_name' => 'string #客服昵称/名称',
            'online_status' => 'int #在线状态 =0离线；=1在线',
            'operator_status' => 'int #客服的状态，=0表示禁用，=1表示启用',
            'reg_time' => 'int #注册时间，时间戳，秒',
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
        $url = $site['url'] . '/user?' . http_build_query($param);
        $data = $manage->getBaseStatistics($url, [], 'GET');
        if (is_array($data) && $data['error'] == 0) {
            $attributes['total'] = $data['data']['total'];
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            foreach ($data['data']['data'] as $key => $value) {
                $data['data']['data'][$key]['reg_time'] = date('Y-m-d H:i:s', $value['reg_time']);
            }
            if (!$attributes['total'])
                return [];
            return $this->lang->set(0, [], $data['data']['data'], $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};