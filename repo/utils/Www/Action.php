<?php
namespace Utils\Www;

use Logic\Define\Lang;
use Redis;
use Slim\Http\Request;

/**
 * @property Request $request
 * @property Lang $lang
 * @property Redis $redis
 */
class Action {

    protected $ci;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    protected $ipProtect = true;

    protected $maintaining = false;

    /**
     * 当前语言ID
     * @var int $language_id
     */
    protected $language_id = 1;
    /**
     * 当前语言名称
     * @var string $language_name
     */
    protected $language_name = '中文';

    /**
     * @var string 图片域名
     */
    protected $pictureDoman = '';

    // public function __construct($ci)
    // {
    //     $this->ci = $ci;
    //     if ($this->beforeActionList) {
    //         foreach ($this->beforeActionList as $method ) {

    //             call_user_func([$this, $method]);

    //         }
    //     }
    // }

    public function init($ci) {
        $this->ci = $ci;
        $website  = $ci->get('settings')['website'];

        //ip黑名单  noCheckBlackIp=true (该接口不需要判断ip黑名单)
        $check_black_ip = \Logic\Set\SystemConfig::getModuleSystemConfig('network_safty')['check_black_ip'];
        if ($check_black_ip && empty($this->noCheckBlackIp)) {
            $res = \Utils\Client::isBlackIp();
            if ($res->getState()) {
                return $res;
            }
        }

        //防止表单重复提交
        $res = $this->verifyFormVersionNo();
        if(!$res){
            return $this->lang->set(885);
        }

        $verify     = $this->auth->verfiyToken();
        $user       = new \Logic\User\User($this->ci);
        $uid        = $this->auth->getUserId();
        // 系统维护性开关
        if (in_array($this->request->getMethod(), ['POST', 'PUT', 'PATCH', "DELETE"]) && \Logic\Set\SystemConfig::getModuleSystemConfig('system')['maintaining']) {

            //维护期间，用户是否可进

            //未登录，用账号查找UID
            if(!empty($uid))
            {
                $info = $user->getUserAuthInfo('id', $uid);
            }else{
                $name = $this->request->getParam('name');
                $info = $user->getUserAuthInfo('name', $name);
            }

            if(strpos($info['auth_status'], 'maintaining_login') === false) return $this->lang->set(5);

        }


        // ip 请求流量保护
        if (isset($website['ipProtect']) && $website['ipProtect'] && $this->ipProtect) {
            $second  = $website['ipProtect']['second'];
            $reqNum  = $website['ipProtect']['requestNum'];
            $methods = $website['ipProtect']['methods'];
            $res = \Utils\Client::getApiProtectByIP('', $second, $reqNum, $methods);
            if (!$res->allowNext()) {
                return $res;
            }
        }

        //多语言取当前语言ID
        $language               = (new \Model\Language)->getCurrentIdName();
        $this->language_id      = $language['language_id'];
        $this->language_name    = $language['language_name'];

        //用户的版本号 与当前版本号不相同  就告诉h5需要刷新页面
        if($uid){
            $key        = 'refresh_h5:'.$uid;
            $value      = $this->ci->redis->get($key);
            $h5_version = \Logic\Set\SystemConfig::getModuleSystemConfig('market')['h5_version'];
            $h5_version = trim($h5_version);

            if($h5_version){
                if(empty($value) || $value != $h5_version){
                    //缓存7天
                    $this->ci->redis->setex($key,604800,$h5_version);
                    return $this->lang->set(550);

                }
            }
        }

        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method) {
                call_user_func([$this, $method]);
            }
        }

        //图片域名
        $this->pictureDoman = $this->ci->get('settings')['upload']['dsn'][$this->ci->get('settings')['upload']['useDsn']]['domain'];
    }

    /**
     * 限制使用环境
     */
    public function verifyRunMode(){
        if(RUNMODE != 'dev') { die;}
    }


    /**
     * 防止表单重复提交
     * @return bool
     */
    public function verifyFormVersionNo(){
        if(isset($_SERVER['HTTP_FORMVERSIONNO']) && $_SERVER['HTTP_FORMVERSIONNO']){
            //这些方法才判断
            if(!in_array($this->request->getMethod(), ['POST', 'PUT', 'PATCH', "DELETE"])) return true;
            $this->auth->verfiyToken();
            $uid       = $this->auth->getUserId();
            //没有获取到用户id
            if(!$uid)  return true;

            $uri       = $_SERVER["PHP_SELF"];
            $method    = substr($uri, strrpos($uri,'/') + 1);
            //根据请求方法名做key
            $redis_key = "formVersionNo:$uid:".$method.$_SERVER['HTTP_FORMVERSIONNO'];
            $res       = $this->redis->incr($redis_key);
            if($res != 1){
                return false;
            }
            //设置过期时间20秒
            $this->redis->expire($redis_key,20);
        }
        return true;
    }

    public function __get($field) {
        if (!isset($this->{$field})) {
            return $this->ci->{$field};
        } else {
            return $this->{$field};
        }
    }

    /**
     * 翻译消息
     * @param $msg
     * @return mixed
     */
    public function translateMsg($msg){
        if($title = json_decode($msg, true)){
            if(is_array($title)){
                return $this->lang->text(array_shift($title), $title);
            }else{
                return $this->lang->text($title);
            }

        }else{
            return $this->lang->text($msg);
        }

    }

}
