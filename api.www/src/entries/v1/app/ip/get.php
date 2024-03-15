<?php

use Utils\Www\Action;
use Utils\GeoIP;
use Utils\Client;

return new class extends Action {
    const HIDDEN = true;
    const TITLE   = '获取当前请求IP以及地区信息';
    const QUERY   = [
        'ip'    => 'string() #ip地址'
    ];
    const SCHEMAS = [
        'ip'           => 'string(required) #IP地址 124.14.120.13',
        'country_code' => 'string(required) #国家代码 CN',
        'country_name' => 'string(required) #国家名称 China',
    ];

    public function run() {
        if (false == ($ip = $this->request->getQueryParam('ip', false))) {
            $ip = Client::getIp();
        }

        $geoipObject = new GeoIP();
        $gi = $geoipObject->geoip_open(false, GEOIP_STANDARD);
        $country_code = $geoipObject->geoip_country_code_by_addr($gi, $ip);
        $country_name = $geoipObject->geoip_country_name_by_addr($gi, $ip);

        $result = [
            'ip'           => $ip,
            'country_code' => $country_code,
            'country_name' => $country_name,
        ];

        return $this->lang->set(0, [], $result);
    }
};
