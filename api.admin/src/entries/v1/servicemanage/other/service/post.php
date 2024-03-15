<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/8
 * Time: 14:55
 */

use Logic\Admin\BaseController;

/*
 * 其他设置 新增客服人员
 *
 * */
return new class extends BaseController
{

    const TITLE = 'post 客服统计新增客服人员';
    const DESCRIPTION = '客服统计客服绩效';
    
    const QUERY = [];
    
    const PARAMS = [
        'account' => 'string #账号',
        'password' => 'string #密码',
        'nick_name' => 'string #昵称/名称',
        'operator_status' => 'int #客服的状态，=0表示禁用，=1表示启用',
    ];
    const SCHEMAS = [
        [
            'find' => 'string #账号',
            'password' => 'string #密码',
            'nick_name' => 'string #昵称/名称',
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
        $url = $site['url'] . '/user';
        $user = $this->playLoad;
        $param['account'] = $user['nick'];
        $param['password'] = md5($this->generate_password());
        $param['operator_status'] = 1;
        $data = $manage->getBaseStatistics($url, $param);
        if ($data['error'] == 0) {
            return $this->lang->set(0, [], $data['data']);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }

    }


    //随机生成密码
    protected function generate_password($length = 8)
    {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
        $password = "";
        for ($i = 0; $i < $length; $i++) {

            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

};