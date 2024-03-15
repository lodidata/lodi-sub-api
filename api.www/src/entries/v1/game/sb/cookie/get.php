<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "沙巴游戏获取cookie";
    const TAGS = "游戏";
    const QUERY = [
        'type' => "string(required) #类型 sb",
    ];
    const SCHEMAS = [
        'cookie' => "string(required) 接口输出cookie值"
    ];

    public function run() {
        $verify = $this->auth->verfiyTokenForSb();
        $type = $this->request->getQueryParam('type');
        if (!$verify->allowNext()) {
            return $this->response
            // ->withHeader('Access-Control-Allow-Origin', '*')
            // ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            // ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withStatus(200)->write('401');
        } else {
            if($type == 'sb') {
                $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : $_GET['token'];
                $config = $this->ci->get('settings')['website'];
                $alias = $config['alias'];
                $cookie = $alias.'saba'.$token;
                return $cookie;
            }
            $uid = $this->auth->getUserId();
            $config = $this->ci->get('settings')['app'];
            $tid = $config['tid'];
            $userName = $tid.'gametes'. dechex($uid + 999) . 'sb' ;
            echo $userName;exit;
            return $this->response
            // ->withHeader('Access-Control-Allow-Origin', '*')
            // ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            // ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withStatus(200)->write($userName);
        }
    }
};