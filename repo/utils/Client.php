<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 15:18
 */

namespace Utils;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException ;
class Client {

    /**
     * 取客户端IP
     * @return [type] [description]
     */
    public static function getIp() {

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (empty($ip)) {
            return '0.0.0.0';
        } else {
            return (explode(',', $ip))[0];
        }

    }

    /**
     * 客户端唯一标识
     *
     * 并不严格
     *
     * @param string $mac
     * @return string|bool
     */
    public static function ClientId($mac = '') {
        if (!is_string($mac)) {
            return false;
        }
        if (empty($mac)) {
            //$remoteIp = self::getClientIp();
            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

            return sha1(join('.', array($agent)));
        } else {
            return sha1($mac);
        }
    }

    public static function isSsl() {  
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){  
            return true;  
        }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {  
            return true;  
        }  
        return false;  
    }

    /**
     * 基于ip流水防护
     */
    public static function getApiProtectByIP($tags = '', $second = 3, $reqNum = 30, $methods = ['PUT', 'POST', 'PATCH', 'DELETE']) {
        global $app;
        $ci = $app->getContainer();
        $config = $ci->get('settings')['website'];
        if(isset($config['antiBrush']) && $config['antiBrush'] === false )
            return true;
        $key = \Logic\Define\CacheKey::$perfix['protectByIP'].'_'.$tags.'_'.self::getIp();
        if (in_array($ci->request->getMethod(), $methods) && $ci->redis->incr($key) > $reqNum) {
            return $ci->lang->set(14);
        }
        $ci->redis->expire($key, $second);
        return $ci->lang->set(0);
    }

    /**
     * 24小时内 操作达到指定的次数
     * 冻结ip
     */
    public static function addBlackIP(){
        $check_black_ip = \Logic\Set\SystemConfig::getModuleSystemConfig('network_safty')['check_black_ip'];
        //IP冻结设置 关了
        if(!$check_black_ip) return;

        global $app;
        $ci         = $app->getContainer();
        $redis_key  = \Logic\Define\CacheKey::$perfix['blackIp']. self::getIp();
        $times      = $ci->redis->incr($redis_key);
        $times == 1 && $ci->redis->expire($redis_key,24*3600);//初始化有效时间24小时

        //到10次
        if ($times == 10) {
            $key    = \Logic\Define\CacheKey::$perfix['blackIpLockedList']. self::getIp();
            self::setBlackIpLockTime($key);
        }
    }

    /**
     * ip黑名单 冻结时间
     * @param $key
     * @return mixed
     */
    public static function setBlackIpLockTime($key){
        global $app;
        $ci         = $app->getContainer();
        //冻结时间 默认一天(数字代表天数，0表示永久)
        $lock_time  = \Logic\Set\SystemConfig::getModuleSystemConfig('network_safty')['lock_time'];

        //redis 里只设2年，避免时间太长 占用redis
        $lock_time == 0 && $lock_time = 365*2;
        $time = 24*3600*$lock_time;

        $res = $ci->redis->setex($key,$time,1);

        self::insertBlackIp(['valid_time'=>$lock_time]);
        return $res;
    }

    /**
     * ip黑名单 插入数据库
     * @param $params
     */
    public static function insertBlackIp($params){
        $ip   = $params['ip'] ?? self::getIp();
        $data['ip']   = \DB::raw("inet6_aton('{$ip}')");
        $accounts_num = \DB::table('user')->whereRaw("login_ip = INET6_ATON(?)",[$ip])->count();
        $data['accounts_num'] = $accounts_num;
        $data['operator']     = $params['operator'] ?? '系统';
        $data['memo']         = $params['memo'] ?? '无效用户';
        $data['valid_time']   = date('Y-m-d H:i:s',strtotime("+{$params['valid_time']} day"));
        \DB::table('ip_black')->insert($data);
    }

    /**
     * 判断是否ip黑名单
     * 只从redis里判断
     * @return mixed
     */
    public static function isBlackIp(){
        global $app;
        $ci         = $app->getContainer();
        $ip         = \Utils\Client::getIp();
        $redis_key  = \Logic\Define\CacheKey::$perfix['blackIpLockedList']. $ip;

        if ($ci->redis->get($redis_key)) {
            return $ci->lang->set(124);
        }
        return $ci->lang->set(0);
    }

    /**
     * 基于用户级流水防护
     */
    public static function getApiProtectByUser($userId, $tags = '', $second = 3, $reqNum = 10, $methods = ['PUT', 'POST', 'PATCH', 'DELETE']) {
        global $app;
        $ci = $app->getContainer();
        $key = \Logic\Define\CacheKey::$perfix['protectByUser'].'_'.$tags.'_'.$userId;
        if (in_array($ci->request->getMethod(), $methods) && $ci->redis->incr($key) > $reqNum) {
            return $ci->lang->set(15);
        }
        $ci->redis->expire($key, $second);
        return $ci->lang->set(0);
    }


    public static function gerIpRegion($ip = '')
    {

        global $app;
        if (empty($ip))
            return;

        $client = new HttpClient();
        try {
            $result = $client->request('GET', 'http://ip-api.com/json/' . $ip, [
                'timeout' => 3.14,
            ]);
            $region = '';
            if ($result->getStatusCode() == 200) {
                $res = (json_decode($result->getBody(), true));
                if ($res['status'] === 'success') {
                    $region = $res;
                }
            }

            return $region;
        } catch (\Exception $e) {
            if ($e instanceof ClientException) {
                $app->getContainer()->logger->error('frontend', ['message' => $e->hasResponse() ? $e->getResponse() : '']);
            } else {
                $app->getContainer()->logger->error('frontend', ['message' => $e->getMessage()]);
            }
            return [];
        }
    }

    /**
     * 获取头部信息
     * @param string $name 头部名称
     * @return mixed
     */
    public static function getHeader($name = '')
    {   
        global $app;
        $header = $app->getContainer()->get('request')->getHeaders();
        if (!empty($name)) {
            return isset($header[$name]) ? $header[$name] : null;
        } 

        return $header;
    }
}