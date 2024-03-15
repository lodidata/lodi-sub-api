<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 11:43
 */


use Logic\Admin\BaseController;

/*
 * 其他设置 修改昵称/名称
 *
 * */
return new class extends BaseController
{

    const TITLE = 'post 客服统计修改昵称/名称';
    const DESCRIPTION = '客服统计修改昵称/名称';

    const QUERY = [

    ];
    
    const PARAMS = [
        'uin' => 'int #需要修改账号id',
        'nick_name' => 'int #新昵称/名称',
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
        $url = $site['url'] . '/user/edit_nick_name';
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