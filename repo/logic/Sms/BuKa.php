<?php
namespace Logic\Sms;
use Logic\Logic;
use Utils\Curl;

/**
 * 不卡短信平台
 * Class BUKa
 */
class BuKa extends Logic {
    /**
     * @var string API接口地址
     */
    private $apiUrl = "https://api.onbuka.com/v3/";
    static $LOG =  LOG_PATH.'/sms/';
    private $errArr = [
        '0'  => 'success',
        '-1' => '认证错误',
        '-2' => 'IP访问受限',
        '-3' => '短信内容含有敏感字符',
        '-4' => '短信内容为空',
        '-5' => '短信内容过长',
        '-6' => '不是模板的短信',
        '-7' => '号码个数过多',
        '-8' => '号码为空',
        '-9' => '号码异常',
        '-10' => '客户余额不足，不能满足本次发送',
        '-11' => '定时时间格式不对',
        '-12' => '由于平台的原因，批量提交出错，请与管理员联系',
        '-13' => '用户被锁定',
        '-14' => 'Field为空或者查询id异常','-15','查询过频繁',
        '-16' => 'timestamp expires',
        '-17' => '短信模版不能为空',
        '-18' => '接口异常',
        '-19' => '认证完成后，需要联系商务经理为您开启短信之旅'
    ];

    /**
     * 短信发送
     * @param string $mobile 短信接收号码，多个号码之间以英文逗号分隔（get最多100个,post最多1000个）
     * @param string $content 发送内容，长度不能超过1024字符，get请求内容需要urlEncode
     * @param string $code 发送的号码
     * @return array
     * {"status":"0","reason":"success","success":"2","fail":"0","array":[{"msgId":"2108021054011000095","number":"91856321412"},{"msgId":"2108021059531000096","number":"91856321413"}]}
     */
    public function sendSms($mobile, $content, $code)
    {
        $data = [
            'numbers'   => $mobile,
            'content'   => $content,
            'senderId'  => $code,
        ];
        return $this->requestParam('sendSms', $data);
    }

    /**
     * 用于获取账户余额接口
     * {"status":"0","reason":"success","balance":"99.990000","gift":"50.00000"}
     */
    public function getBalance()
    {
        return $this->requestParam('getBalance', [], false);
    }

    /**
     * 查询指定msgId集合的发送结果
     * @param $msgids
     * @return array
     * status    发送状态：0发送成功，-1：发送中，1：发送失败
     * {"status":"0","reason":"success","success":"2","fail":"0","sending":"0","nofound":"0","array":[{"msgId":"2108021054011000095","number":"91856321412","receiveTime":"2021-02-12T09:30:03+08:00","status":"0"},{"msgId":"2108021059531000096","number":"91856321413","receiveTime":"2021-02-12T09:30:03+08:00","status":"0"}]}
     */
    public function getReport($msgids){
        $data = [
            'msgIds'   => $msgids,
        ];
        return $this->requestParam('getReport', $data, false);
    }

    /**
     * 查询开始时间到结束时间这个时间段内已经发送完成的短信结果
     * @param string $startTime 2021-02-12 09:30:00
     * @param string $endTime  2021-02-12 19:30:00
     * @return array
     */
    public function getSentRcd($startTime, $endTime)
    {
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $default_timezone = date_default_timezone_get();
        date_default_timezone_set("Etc/GMT+4");
        $data = [
            'startTime'  => date("Y-m-d\TH:i:s+08:00", $startTime),
            'endTime'    => date("Y-m-d\TH:i:s+08:00", $endTime),
            'startIndex' => 0
        ];
        date_default_timezone_set($default_timezone);
        return $this->requestParam('getReport', $data, false);
    }

    /**
     * API接口请求
     * @param string $action 接口名称
     * @param array $params 参数
     * @return array
     */
    public function requestParam($action, $params, $isPost = true)
    {
        $config = $this->ci->get('settings')['website']['captcha']['dsn']['BuKa'];
        if(empty($config)){
            return ['status' => 10000, 'message' => 'config error'];
        }
        if($params){
            $params['appId'] = $config['appId'];
        }
        $timestamp = time();
        $sign = md5($config['apiKey'].$config['apiSecret'].$timestamp);
        $header = [
            'Content-Type:application/json;charset=UTF-8',
            'Sign:'.$sign,
            'Timestamp:'.$timestamp,
            'Api-Key:'.$config['apiKey']
        ];

        $url = $this->apiUrl.$action;

        //print_r($header).PHP_EOL;
       // print_r($params).PHP_EOL;
        if($isPost){
            $res = Curl::post($url, null, $params, null,true, $header);
        }else{
            if($params){
                $querystring = http_build_query($params,'', '&');
                $url = $url . '?' . $querystring;
            }
            $res = Curl::get($url, null,true, $header);
        }
        //var_dump($res);
        if($res['status'] == 200){
            $result = json_decode($res['content'], true);
            if($result['status'] != 0){
                $result['message'] = $this->errArr[$result['status']];
            }
            $logs = [
                'url' => $url,
                'json' => $params,
                'response' => $result
            ];
            self::addLog($logs);
            return $result;
        }else{
            return ['status' => 10001, 'message' => 'api service error'];
        }
    }

    public static function addLog($data){
        $file = self::$LOG.'buka-'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        $date['logTime'] = date('Y-m-d H:i:s');
        $str = urldecode(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).PHP_EOL;
        @fwrite($stream, $str);
        @fclose($stream);
    }
}