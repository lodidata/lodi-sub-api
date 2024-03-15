<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/1
 * Time: 17:35
 */

namespace Logic\Service;


class Manage extends \Logic\Logic
{
    const IM_DEFAULT_EXPIRE = 3600 * 24; //Im默认的 token过期时间
    public $token;
    public $node_id;
    public $key;
    public $url;
    public $app_id;
    public $app_secret;

    public function __construct($ci)
    {
        parent::__construct($ci);
        $site = $this->ci->get('settings')['ImSite'];
        $this->node_id = $site['node_id'];
        $this->key = $site['key'];
        $this->url = $site['url'];
        $this->app_id = $this->ci->get('settings')['pusherio']['app_id'];
        $this->app_secret = $this->ci->get('settings')['pusherio']['app_secret'];
//        $this->token = $this->getToken();
    }

    /**
     * 获取token
     */
    public function getToken()
    {
        global $app;
        $token = $this->redis->get(\Logic\Define\CacheKey::$perfix['imToken'] . $this->node_id);
        if (empty($token)) {
            $res = \Requests::request($this->url . '/token', [], [
                'node_id' => $this->node_id,
                'key' => $this->key,
            ], 'POST');
            $json = $res->body;
            $data = json_decode($json, true);
            if ($data['error'] == 0) {
                $token = $data['data']['token'];
                $app->getContainer()->redis->setex(\Logic\Define\CacheKey::$perfix['imToken'] . $this->node_id, self::IM_DEFAULT_EXPIRE, $token);
                return $token;
            } else {
                return $data;
            }

        }
        return $token;
    }


    /**
     * 统计查询基类方法
     * @param $url
     * @param array $param
     * @return mixed
     */
    public function getBaseStatistics($url, $param = [], $type = 'POST')
    {
        $token = $this->token;
        if (is_array($token)) return $token;//获取token 判断返回的是否为数组，如果为数组，则返回数组错误信息，并返回
        $res = \Requests::request($url,
            [
                'token' => $token,
            ], $param, $type);
        $json = $res->body;
        $data = json_decode($json, true);
        return $data;
    }

    /*
     * 获取用户角色类型 1为正式用户，2为试玩用户，3为游客
     */
    public function getUserType($user_id)
    {
        $type = 1;
        if ($user_id >= 20000000000) {
            $type = 3;
        } else if ($user_id > 10000000000 && $user_id < 20000000000) {
            $type = 2;
        }
        return $type;
    }


    /*
     * 获取hashids 对象
     */
    public function getHashids(){
        $hashids = new \Hashids\Hashids($this->app_id . $this->app_secret, 8, 'abcdefghijklmnopqrstuvwxyz');
        return $hashids;
    }



}