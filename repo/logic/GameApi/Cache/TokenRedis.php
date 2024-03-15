<?php

namespace Logic\GameApi\Cache;


class TokenRedis
{
    protected $redis;
    public function __construct()
    {
        global $app;
        $ci = $app->getContainer();
        $this->redis = $ci->redis;
    }
    /**
     * 设置token缓存
     * @param $params
     * @param $token
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public  function setToken($params, $token){
        
        $key         = "game:token:{$params['type']}:{$params['id']}";
        $expire_time = $params['expire'];
        //token过期时间就通过redis有效期来控制
        $this->redis->set($key, $token);
        $this->redis->expire($key, $expire_time);
    }

    /**
     * 验证token是否存在
     * @param $params
     * @param $token
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public  function tokenExist($params,$token){
        $key       = "game:token:{$params['type']}:{$params['id']}";
        $old_token = $this->redis->get($key);
        if($old_token){
            return $old_token == $token;
        }
        return false;
    }

    /**
     * 删除token
     * @param $params
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public  function delToken($id){
        
        $key         = "game:token:game:{$id}";
        //删除token
        $this->redis->del($key);
    }

}