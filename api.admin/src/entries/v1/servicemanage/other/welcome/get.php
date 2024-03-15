<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 15:32
 */


use Logic\Admin\BaseController;

/*
 * 其他设置 获取欢迎语
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 其他设置获取欢迎语';
    const DESCRIPTION = '其他设置获取欢迎语';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'id' => 'int #数据id',
            'node_id' => 'string #公司ID',
            'welcome' => 'string #欢迎语',
            'leave_msg' => 'int #留言语',
        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {


        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/welcome_msg?';
        $data = $manage->getBaseStatistics($url, [], 'GET');

        if ($data['error'] == 0) {
            $attributes['total'] = 1;
            $attributes['number'] = 1;
            $attributes['size'] = 20;
            if (!$attributes['total'])
                return [];
            if (!$attributes['total'])
                return [];
            return $this->lang->set(0, [], $data['data'], $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};