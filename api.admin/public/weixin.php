<?php
exit;
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, X-Request-Uri, Content-Type, Accept, Origin, Authorization, pl, mm, av, sv, uuid');
echo 11;
$corpid = 'wx93f6b1006ad2ef2b';
$corpsecret = '85e8dd69d75c572b324175a086a3dd05';

$res_token = get( "https://api.weixin.qq.com/cgi-bin/token?appid={$corpid}&secret={$corpsecret}&grant_type=client_credential");
if (isset($res_token['errcode'])) {
    exit(json_encode(['status' => 886, 'msg' => '获取token失败', 'data' => []]));
}else{
    $res_token=json_decode($res_token,true);
    $access_token = $res_token['access_token'];
    $expires_in=$res_token['expires_in']??'';

    $res_ticket = get( "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi");

    if (isset($res_ticket['errcode']) && $res_ticket['errcode'] != 0) {
        exit(json_encode(['status' =>886, 'msg' => '获取ticket失败', 'data' => []]));
    }else{
        $res_ticket=json_decode($res_ticket,true);

        $jsapi_ticket = $res_ticket['ticket']??'';

        $params = array(
            'jsapi_ticket' => $jsapi_ticket,
            'url' => $_REQUEST['url']??'' ,//当前网页的URL，不包含#及其后面部分（需要前端传参）
            'timestamp' => time(),
            'nonceStr' => createStr()
        );

        $data['timestamp'] = $params['timestamp'];
        $data['nonceStr'] = $params['nonceStr'];
        $data['appId'] = $corpid;
//        var_dump($params);
        $data['signature'] = getSign($params);
        exit(json_encode(['status' => 1, 'msg' => '成功', 'data' => $data]));
    }
}


//生成签名
function getSign($params)
{
    ksort($params);

    $string = '';
    foreach ($params as $keyVal => $param) {
        $string .= $keyVal . '=' . $param . '&';
    }
//    var_dump(rtrim($string, '&'));
//    var_dump(sha1(rtrim($string, '&')));
    return sha1(rtrim($string, '&'));
}

function createStr()
{
    $nstr = 'WERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm';
    $nonce_str = substr(str_shuffle($nstr), 0, 32);
    return $nonce_str;
}


//获取token
function get($urlPay, $cacert_url = null)
{
    $curl = curl_init($urlPay);
    curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $cacert_url ? 1 : 0);//SSL证书认证
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $cacert_url ? 2 : 0);//严格认证
    //证书地址
    if ($cacert_url && file_exists($cacert_url)) {
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);
    }
    $responseText = curl_exec($curl);
//    print_r( curl_error($curl) );
    curl_close($curl);
    return $responseText;
}

?>




