<?php
use Logic\Admin\BaseController;
//获取im配置信息
return new class extends BaseController {
    public function run() {
        $site = $this->ci->get('settings')['ImSite'];
        /*$serviceSet = DB::table('service_set')->where('node_id', $site['node_id'])->first();
        $site['access_way'] = $serviceSet->access_way;
        $site['link'] = $serviceSet->link;*/
        $site['url'] = $site['client_url'];

        $appId = $this->ci->get('settings')['pusherio']['app_id'];
        $appSecret = $this->ci->get('settings')['pusherio']['app_secret'];
        $site['app_id'] = $appId;
        $site['app_Secret'] = $appSecret;
        unset($site['key']);
        return $this->lang->set(0,[],$site);
    }
};