<?php

use Utils\Www\Action;

//获取im配置信息和超管配置的客户（厅主配置的客服信息）
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "获取im配置信息和超管配置的客户";
    const DESCRIPTION = "获取im配置信息和超管配置的客户 用户登录时要传TOKEN";
    const TAGS = '客服';
    const HEADERS = [
        'HTTP_UUID' => "string() #uuid 唯一标识"
    ];
    const SCHEMAS = [
        'node_id'           => 'int(required) #IM node_id',
        'key'               => 'string(required) #IM key',
        'url'               => 'string(required) #IM url',
        'client_url'        => 'string(required) #IM 客服端URL',
        'port'              => 'string(required) #web端口',
        'Android_port'      => 'string(required) #安卓端口',
        'ios_port'          => 'string(required) #ios端口',
        'web_user_port'     => 'string(required) #websocket协议的客户连接端口',
        'web_service_port'  => 'string(required) #websocket协议的客服连接端口为b',
        "access_way"        => "int(required) #客服接入方式，默认1 客服系统。2 在线客服代码链接",
        "link"              => "string(required) #链接地址",
        "no_login_trial_service" => "int(required) #未登录和试玩用户可联系客服开关 1开，0关",
        "user_id"           => "int() #用户ID 未登录null",
        "user_type"         => "string() #用户类型 1 正式用户 2 试玩用户 3游客"
    ];
    public function run()
    {

        return $this->lang->set(2223);
        /*$site = $this->ci->get('settings')['ImSite'];//获取im配置数据 初始化返回数据

        $start_data = \Logic\Set\SystemConfig::getModuleSystemConfig('register');

        // 未登录和试玩用户可联系客服开关
        $site['no_login_trial_service'] = $start_data['no_login_trial_service'];
        //超管配置信息
        $serviceSetCache = $this->redis->get(\Logic\Define\CacheKey::$perfix['serviceNodeId'] . '_' . $site['node_id']);
        if (empty($serviceSetCache)) {
            $serviceSet = \DB::table('service_set')->where('node_id', $site['node_id'])->first();
            if (empty($serviceSet)) {
                return $this->lang->set(2223);
            }
            $access_way = $serviceSet->access_way;
            $link = $serviceSet->link;
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['serviceNodeId'] . '_' . $site['node_id'], 120, json_encode($serviceSet));

        } else {
            $serviceSet = json_decode($serviceSetCache, true);
            $access_way = $serviceSet['access_way'];
            $link = $serviceSet['link'];
        }


        $site['access_way'] = $access_way;
        $site['link'] = $link;

        $server = $_SERVER;
        //判断是否有传Authorization，如果没传则为游客
        if (!isset($server['HTTP_AUTHORIZATION'])) {
            if ($site['access_way'] == 1 && !$site['no_login_trial_service']) {
                return $this->lang->set(2221);
            }
            //若是游戏同UUID为当前同一个人
            $device = $this->request->getHeaders()['HTTP_UUID'] ?? '';
            $device = is_array($device) ? array_shift($device) : '';
            $user_type = 3;
            if($device && ($user_id = $this->redis->get(\Logic\Define\CacheKey::$perfix['Tourist'] . $device))){
                $original_user_id = $user_id; //原始user_id
            }else{
                //判断是否存在游客缓冲键，如果没有则初始化为200亿
                $key = \Logic\Define\CacheKey::$perfix['visitorUserId'];
                $visitor_user_id = $this->redis->get(\Logic\Define\CacheKey::$perfix['visitorUserId']);
                if (empty($visitor_user_id)) {
                    $this->redis->set($key, 20000000000);
                    $user_id = 20000000000; //游客原user_id 和加百亿后的 user_id值相等。
                } else {
                    $user_id = $this->redis->incr($key);
                }
                $original_user_id = $user_id; //原始user_id
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['Tourist'] . $device,86400,$user_id);
            }

        } else {
            $this->auth->verfiyToken();

            $user_id = $this->auth->getUserId(); //user_id
            $original_user_id = $user_id; //原始user_id
            $user_type = 1;
            //判断是否是试玩用户
            if ($this->auth->getTrialStatus()) {
                if ($site['access_way'] == 1 && !$site['no_login_trial_service']) {
                    return $this->lang->set(2221);
                }
                $user_id = $user_id + 10000000000; //加百亿后的user_id

                $user_type = 2;
            }
        }


        $app_id = $this->ci->get('settings')['pusherio']['app_id'];
        $app_secret = $this->ci->get('settings')['pusherio']['app_secret'];
        $hashids = new \Hashids\Hashids($app_id . $app_secret, 8, 'abcdefghijklmnopqrstuvwxyz');
        $hashids_user_id = $hashids->encode($user_id);
        $site['user_id'] = base_convert($hashids_user_id, 36, 10);
        $site['user_id'] = (int)$site['user_id'];

        if ($original_user_id == 0 || $user_id == 0) {
            return $this->lang->set(2222);
        }


        //新增sock会话用户id对照生成记录
        $insert_data['original_user_id'] = $original_user_id;
        $insert_data['plus_user_id'] = $user_id;
        $insert_data['encrypt_user_id'] = $site['user_id'];
        $insert_data['user_type'] = $user_type;
        $server_user_socket = new \Model\Admin\ServiceUserSocket();
        $server_user_count = $server_user_socket::where('original_user_id', $original_user_id)->count();
        if (!$server_user_count && !$server_user_socket::create($insert_data)) {
            return $this->lang->set(2220);
        }


        $url = $site['client_url'];
        //判断是否为带有ip字符串的url
        if (filter_var(substr($url, 7), FILTER_VALIDATE_IP)) {
            $site['url'] = substr($url, 7);
        } else if (filter_var(substr($url, 8), FILTER_VALIDATE_IP)) {
            $site['url'] = substr($url, 8);
        } else {
            $site['url'] = $url;
        }

        //如果access_way=2 是为第三方客服，则把url的值改为$site['link']，此处为兼容线上ios包出现的取值错误bug
        if ($site['access_way'] == 2) {
            $site['url'] = $site['link'];
        }
        $site['user_type'] = $user_type;//用户类型 1 正式用户 2 试玩用户 3游客
        //$serviceSet = DB::table('service_set')->where('node_id', $site['node_id'])->first();


        unset($site['key']);
        return $this->lang->set(139, [], $site);*/

    }
};