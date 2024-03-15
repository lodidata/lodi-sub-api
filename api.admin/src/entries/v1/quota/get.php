<?php
/**
 * 额度信息展示
 * @author Taylor 2019-01-21
 */

use Logic\Admin\BaseController;

return new class extends BaseController
{

   protected $beforeActionList = [
       'verifyToken', 'authorize',
   ];

    public function run()
    {
//        $quota = new \Logic\User\Quota($this->ci);
//        $data = $quota->requestPaySit('getQuota');
//        if ($data['state'] != 0) {
//            return $this->lang->set($data['state'], [$data['message']]);
//        } else {
//            return $this->lang->set(0, [], $data['data']);
//        }
        return $this->lang->set(0, [], []);
        $customer = \DB::table('pay_site_config')->first(['customer', 'app_secret']);
        $res = \DB::table('quota')
            ->select('total_quota', 'use_quota', 'surplus_quota')
            ->where('customer', '=', $customer->customer)
            ->get()
            ->first();
        return $this->lang->set(0, [], $res);
    }
};