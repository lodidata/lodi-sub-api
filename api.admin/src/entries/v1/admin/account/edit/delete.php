<?php

use Logic\Admin\Log;
use Model\Admin\Role;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '刪除管理員';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $admin = DB::table('admin_user')->find($id);
        if (!$admin) {
            return $this->lang->set(10014);
        }

        /*$site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        $url = $site['url'] . '/user/find';
        $service_data = $manage->getBaseStatistics($url, ['account' => $admin->username]);
        $service_user = $service_data['data'];
        if (!empty($service_user) && $service_user['operator_status'] == 1) {
            return $this->lang->set(10582);
        }*/


        (new \Logic\Admin\Cache\AdminRedis($this->ci))->removeAdminUserCache($id);
        DB::beginTransaction();
        try {
            /*============================日志操作代码================================*/
            $info = DB::table('admin_user')
                ->find($id);
            /*============================================================*/
            //删除
            DB::table('admin_user')->where('id', $id)->delete();

            DB::table('admin_user_role_relation')->where('uid', $id)->delete();

            DB::commit();


            /*============================日志操作代码================================*/
            (new Log($this->ci))->create($id, $info->username, Log::MODULE_USER, '管理员列表', '管理员列表', '删除', 1, "账号：{$info->username}");
            /*============================================================*/
        } catch (\Exception $e) {
            DB::rollback();
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

};
