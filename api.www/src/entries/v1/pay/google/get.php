<?php
use Utils\Www\Action;

//谷歌支付回调接口
return new class extends Action
{
    const TITLE = "谷歌支付回调地址";
    const DESCRIPTION = "用于谷歌支付回调的接口";
    const TAGS = '支付';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        //获取code
        $code = $this->request->getParam('code');
        if (empty($code)) {
            echo "code is null";
            exit(1);
        }
        echo "code=".$code;
        echo "<br>";
        exit(1);

        //发送post请求获取刷新令牌
        $client_id = '457410488032-0t16dl3atnbsr94v8m5ut445ras1bl85.apps.googleusercontent.com';
        $client_secret = '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';
        $redirect_url = 'https://lodi-api-www.caacaya.com/pay/google';

        $url = "https://accounts.google.com/o/oauth2/token";
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_url
        ];
        $res = \Utils\Curl::post($url, null, $params);
        if (isset($res['status']) && $res['status'] == 200) {
            $res_data = json_decode($res['content'], true);
//            $this->logger->debug('google_pay_redirect', $res_data);
            var_dump($res_data);
        }
        //保存刷新令牌 refresh_token
//        $this->redis->get(\Logic\Define\CacheKey::$perfix['google_pay_refresh_token_ncg789']);
    }

};
