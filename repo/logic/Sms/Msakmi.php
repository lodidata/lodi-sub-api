<?php
namespace Logic\Sms;
use Logic\Logic;
use Utils\Curl;

class Msakmi extends Logic {
    private $apiUrl = "http://47.243.168.18:9090/sms/batch/v2";
    static $LOG =  LOG_PATH.'/sms/';
    private $errArr = [
        '00000' => '请求成功',
        'F0001' => '参数appkey未填写',
        'F0002' => '参数appcode未填写',
        'F0003' => '参数phone未填写',
        'F0004' => '参数sign未填写',
        'F0005' => '参数timestamp未填写',
        'F0006' => 'appkey不存在',
        'F0007' => '账号已经关闭',
        'F0008' => 'sign检验错误',
        'F0009' => '账号下没有业务',
        'F0010' => '业务不存在',
        'F0011' => '手机号码超过1000个',
        'F0012' => 'timestamp不是数字',
        'F0013' => 'timestamp过期超过5分钟',
        'F0014' => '请求ip不在白名单内',
        'F0015' => '余额不足',
        'F0016' => '手机号码无效',
        'F0017' => '没有可用的业务',
        'F0022' => '参数msg未填写',
        'F0023' => 'msg超过了1000个字',
        'F0024' => 'extend不是纯数字',
        'F0025' => '内容签名未报备/无签名',
        'F0039' => '参数sms未填写',
        'F0040' => '参数sms格式不正确',
        'F0041' => '短信条数超过1000条',
        'F0050' => '无数据',
        'F0100' => '未知错误',
    ];

    /**
     * 短信发送
     * @param string $mobile 短信接收号码，多个号码之间以英文逗号分隔（get最多100个,post最多1000个）
     * @param string $content 发送内容，长度不能超过1024字符，get请求内容需要urlEncode
     * @param string $code 验证码
     * @return array
     * 成功响应： {"code": "00000","desc": "提交成功","uid": "de859f53a5774dce9fce66712626e5d6","result":[{"status":"00000","phone":"14646464","desc":"提交成功"},{}]}
     */
    public function sendSms($mobile, $content, $code = '')
    {
        $data = [
            'phone'   => trim($mobile),
            'msg'   => urlencode($content),
        ];
        return $this->requestParam($data);
    }

    /**
     * API接口请求
     * @param array $params 参数
     * @return array
     */
    public function requestParam($params)
    {
        $config = $this->ci->get('settings')['website']['captcha']['dsn']['Msakmi'];
        if(empty($config)){
            return ['status' => 10000, 'message' => 'config error'];
        }
        $params['appkey'] = $config['appkey'];
        $params['appsecret'] = $config['appsecret'];
        $params['appcode'] = $config['appcode'];
        $header = [
            'Content-Type:application/x-www-form-urlencoded;charset=UTF-8',
        ];

//        $queryString = http_build_query($params,'', '&');
        $queryString = "";
        foreach ($params as $k => $v) {
            $queryString .= $k."=".$v."&";
        }
        $queryString = substr_replace($queryString, "", -1);

        $url = $this->apiUrl . '?' . $queryString;
        $res = Curl::get($url, null,true, $header);
        if($res['status'] == 200){
            $result = json_decode($res['content'], true);
            if($result['code'] != 0){
                $result['message'] = $this->errArr[$result['code']] ?? $this->errArr['F0100'];
            }
            $logs = [
                'url' => $url,
                'json' => $params,
                'response' => $result
            ];
            self::addLog($logs);
            $result['status'] = $result['code'];
            return $result;
        }else{
            return ['status' => 10001, 'message' => 'api service error'];
        }
    }

    public static function addLog($data){
        $file = self::$LOG.'Msakmi-'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        $date['logTime'] = date('Y-m-d H:i:s');
        $str = urldecode(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).PHP_EOL;
        @fwrite($stream, $str);
        @fclose($stream);
    }

}