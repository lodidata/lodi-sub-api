<?php

use Utils\Admin\Action;
use Logic\Admin\AdminToken;
use Respect\Validation\Validator as v;


return new class extends Action {
    const TITLE = "登录接口";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "文档编写中";


    public function run() {
        $params = $this->request->getParsedBody();
        
        //图形验证码失败
        if(!empty($params['token']) && !empty($params['code']))
        {
            $result = (new \Logic\Captcha\Captcha($this->ci))->validateImageCode($params['token'], $params['code']);
            if(empty($result)) return $this->lang->set(10045);
        }
        


        $params['username'] = isset($params['username']) ? $params['username'] : '';
        $validation = $this->validator->validate($this->request, [
            'username' => v::noWhitespace()
                ->length(4, 16)
                ->setName('用户名'),
            'password' => v::noWhitespace()
                ->length(6, 32)
                ->setName('密码'),
        ]);

        if (!$validation->isValid()) {
            //登陆日志
            (new \Logic\Admin\Log($this->ci))->create(null, $params['username'], \Logic\Admin\Log::MODULE_USER, '登陆', '后台登陆', $params['username'].'登陆',0,'失败--检验不合法');

            return $validation;
        }

        $jwt = new AdminToken($this->ci);
        $ipLimit = $jwt->limitCurrentIp($params['username']);
        if($ipLimit !== false) {
            return $this->lang->set($ipLimit);
        }
        $jwtconfig = $this->ci->get('settings')['jsonwebtoken'];
        $digital = intval($jwtconfig['uid_digital']);
        $token = $jwt->createToken($params, $jwtconfig['public_key'], (int)$jwtconfig['expire'], $digital);
        $temp = $token->lang->get();
        if(isset($temp[1]) && ($temp[1] == 1 || $temp[1] == 0)) {
            //登陆日志
            (new \Logic\Admin\Log($this->ci))->create(null, $params['username'], \Logic\Admin\Log::MODULE_USER, '登陆', '后台登陆', $params['username'].'登陆',1,$temp[1]);
        }else {
            //登陆日志
            (new \Logic\Admin\Log($this->ci))->create(null, $params['username'], \Logic\Admin\Log::MODULE_USER, '登陆', '后台登陆', $params['username'].'登陆',0,json_encode($temp));
        }
        return $token;
    }
};
