<?php
/**
 * 从超管后台请求厅对应的公告
 */
namespace Logic\User;

use Utils\Curl;
class Notice extends \Logic\Logic {

    public $payDir = [
        'getNoticeList' => ['dir' => 'notice/api', 'method' => 'GET'],//获取公告列表
        'getScrollList' => ['dir' => 'notice/scroll', 'method' => 'GET'],//获取滚动公告列表
        'getNoticeType' => ['dir' => 'notice/type/api', 'method' => 'GET'],//获取公告类型
    ];

    //获取配置
    public function requestPaySit(string $action, array $data = []) {
        $customer = \DB::table('pay_site_config')->first(['customer', 'app_secret']);

        if (in_array($action, array_keys($this->payDir)) && $customer->app_secret) {
            $request = $this->ci->get('settings')['website']['api_common_url'];
            $request = is_array($request) ? $request[0] : $request;

            $url = $request . $this->payDir[$action]['dir']. '?customer='.$customer->customer;
            $data['sign'] = md5(http_build_query($data) . $customer->app_secret);

            switch ($this->payDir[$action]['method']) {
                case 'GET':
                    $res = Curl::get($url . '&' . http_build_query($data));
                    break;
                default :
//                    $res = Curl::post($url, null, $data, $this->payDir[$action]['method']);
            }
            if ($res) {
                return json_decode($res, true);
            }
        }
        return [];
    }

    //获取公告列表
    public function getNoticeList($action, $params) {
        return $this->requestPaySit($action, $params);
    }
}