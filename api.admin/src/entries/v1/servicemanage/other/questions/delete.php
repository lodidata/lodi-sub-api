<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 15:25
 */


use Logic\Admin\BaseController;

/*
 * 其他设置 删除问题类型
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PATCH 其他设置删除问题类型';
    const DESCRIPTION = '删除问题类型';
    
    const QUERY = [
        'id' => 'int #需要修改问题类型id',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run($id)
    {

        $param['id'] = $id;
        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/question/delete';
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