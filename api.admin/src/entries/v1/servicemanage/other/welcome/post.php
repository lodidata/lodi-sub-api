<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 16:21
 */

use Logic\Admin\BaseController;

/*
 * 其他设置 设置欢迎语
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PATCH 其他设置设置欢迎语';
    const DESCRIPTION = '设置欢迎语';
    
    const QUERY = [];
    
    const PARAMS = [
        'welcome' => 'string #欢迎语',
        'leave_msg'=>'int #留言语',
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
        $url = $site['url'] . '/welcome_msg';
        $data = $manage->getBaseStatistics($url,$param);
        if ($data['error'] == 0) {
            return $this->lang->set(0);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581,[$data['msg']]);
        }


    }
};