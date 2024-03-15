<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/9
 * Time: 17:22
 */


use Logic\Admin\BaseController;

/*
 * 其他设置 问题开启、关闭
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PATCH 其他设置问题开启、关闭';
    const DESCRIPTION = '问题开启、关闭';

    const QUERY = [

    ];

    const PARAMS = ['question_is_open' => 'int #是否开启问题；=0表示关闭；=1表示开启',];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {

        $param = $this->request->getParams();
        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/question/open_close';
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