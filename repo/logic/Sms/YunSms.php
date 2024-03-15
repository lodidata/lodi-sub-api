<?php
namespace Logic\Sms;
use Logic\Logic;
use Utils\Curl;

/**
 * 云通讯平台
 * http://www.quanqiusms.com/
 * Class YunSms
 */
class YunSms extends Logic {
    /**
     * @var string API接口地址
     */
    private $apiUrl = "http://api.quanqiusms.com/api/sms/mtsend";
    static $LOG =  LOG_PATH.'/sms/';
    private $errArr = [
        '0' => '请求成功',
        '1' => '应用不可用或key错误',
        '2' => '参数错误或为空',
        '3' => '余额不足',
        '4' => '内容为空或包含非法关键词',
        '5' => '内容过长',
        '6' => '号码有误',
        '7' => '群发号码数量不得超过50000个',
        '8' => 'sourceaddress必须为3-10位数字或英文字母',
        '9' => 'IP非法',
        '88' => '请求失败',
        '99' => '系统错误',
    ];

    /**
     * 短信发送
     * @param string $mobile 短信接收号码，多个号码之间以英文逗号分隔（get最多100个,post最多1000个）
     * @param string $content 发送内容，长度不能超过1024字符，get请求内容需要urlEncode
     * @param string $code 验证码
     * @return array
     * {"result":"请求成功","messageid":"20d6c660bd664c65bef20026564b0b79","code":"0"}
     */
    public function sendSms($mobile, $content, $code = '')
    {
        $data = [
            'phone'   => $mobile,
            'content'   => urlencode($content),
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
        $config = $this->ci->get('settings')['website']['captcha']['dsn']['YunSms'];
        if(empty($config)){
            return ['status' => 10000, 'message' => 'config error'];
        }
        $params['appkey'] = $config['appkey'];
        $params['secretkey'] = $config['secretkey'];
        $header = [
            'Content-Type:application/x-www-form-urlencoded;charset=UTF-8',
        ];

        $url = $this->apiUrl;
        $queryString = http_build_query($params,'', '&');
        //print_r($header).PHP_EOL;
       // print_r($params).PHP_EOL;
            $res = Curl::commonPost($url, null, $queryString, $header,true);
        //var_dump($res);
        if($res['status'] == 200){
            $result = json_decode($res['content'], true);
            if($result['code'] != 0){
                $result['message'] = $this->errArr[$result['code']];
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
        $file = self::$LOG.'yunsms-'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        $date['logTime'] = date('Y-m-d H:i:s');
        $str = urldecode(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).PHP_EOL;
        @fwrite($stream, $str);
        @fclose($stream);
    }
}