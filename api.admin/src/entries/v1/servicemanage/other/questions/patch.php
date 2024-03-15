<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 15:06
 */


use Logic\Admin\BaseController;

/*
 * 其他设置 禁用、启用问题类型
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PATCH 其他设置禁用、启用问题类型';
    const DESCRIPTION = '禁用、启用问题类型';
    
    const QUERY = [];
    
    const PARAMS = [
        'question_status' => 'int #问题状态；=0表示禁用；=1表示启用',
        'id' => 'int #需要修改问题类型id'
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
        $url = $site['url'] . '/question/edit_status';
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