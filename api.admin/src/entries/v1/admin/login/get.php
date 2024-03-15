<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Utils\Www\Action;
use Model\Admin\AdminUser;
use Logic\Admin\Admin;
use Logic\Admin\BaseController;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = "文档编写中";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "文档编写中";



    public function run() {
        $user = AdminUser::where('status', '1')
//            ->where('username', $data['username'])
            ->first()
            ->toArray();
        print_r($user);exit;
        return $this->admin->test();
        $cache = $this->redis;
        $cache->set('test',1111);
        $test['test'] =  $cache->get('test');
        return $test;
//        return $test;
//        $jwt = new AdminToken($this->ci);

//        return $jwt->verifyToken();

        $data = AdminUser::where('status', '1')
//            ->where('position', 'home')
//            ->where('approve', 'pass')
//            ->where('pf', $this->request->getQueryParam('type', 1) == 1 ? 'pc' : 'h5')
//            ->where('status', 'enabled')
            ->get()
            ->toArray()
        ;
        $data = (new AdminLogic())->matchUser('lebo10',123456);
        return $data;
    }
};