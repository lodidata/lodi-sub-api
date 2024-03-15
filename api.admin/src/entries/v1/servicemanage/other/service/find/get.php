<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/4
 * Time: 14:08
 */

use Logic\Admin\BaseController;

/*
 * 客服统计 根据账号获取客服信息
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 根据账号获取客服信息';
    const DESCRIPTION = '根据账号获取客服信息';

    const QUERY = [
        'find' => 'string #账号',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'node_id' => 'int #公司ID',
            'find' => 'string #客服账号',
            'password' => 'string #密码',
            'nick_name' => 'string #昵称',
            'operator_status' => 'int #客服的状态，=0表示禁用，=1表示启用',
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
        $url = $site['url'] . '/user/find';
        $data = $manage->getBaseStatistics($url, $param);
        //查询客户平台在超管配置的客服信息数据
        $serviceSet = DB::table('service_set')->where('node_id', $site['node_id'])->first();
        $result = $data['data'];
        $result['access_way'] = $serviceSet->access_way;
        $result['link'] = $serviceSet->link;
        if ($data['error'] == 0) {
            $attributes['total'] = count($result);
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            if (!$attributes) {
                return [];
            }
            return $this->lang->set(0, [], $result, $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(0, [], []);
        }


    }
};