<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\Define\Lang;

return new class extends Action
{
    const TITLE = "忘记登录密码-获取表单";
    const TAGS = "登录注册";
    const QUERY = [
        "name" => "string(required) #用户账号或手机号",
    ];
    const SCHEMAS = [
        "type"          => "int(required) #类型 1:有密保工具,2:未绑定密保工具",
        "telphone_code" => "string(required) #手机号 +86-13512345678",
        "list"          => [
            "mobile" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #手机号（1：有，0：没有）",
                "value"  => "string(required) #显示 ****6150",
            ],
            "email" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #邮箱（1：有，0：没有）",
                "value"  => "string(required) #显示 *****23@email.com",
            ],
            "question" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #安全问题（1：有，0：没有）",
                "value"  => "string(required) #显示值 我的出生日期?",
            ],
        ],
    ];


    public function run() {
        $name = $this->request->getQueryParam('name');
        $validator = $this->validator->validate(compact(['name']), [
            'name' => V::username()
                       ->setName($this->lang->text("username")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $name_mobile =\Utils\Utils::RSAEncrypt($name);
        $user = \Model\User::where('name', $name)->orWhere('mobile', $name_mobile)->first();
        if (empty($user)) {
            return $this->lang->set(51);
        }

        if ($user['state'] > 1) {
            return $this->lang->set(130);
        }

        $safety = new \Logic\User\Safety($this->ci);
        $safeList = $safety->getTools($user['id']) ?: null;

        // $this->redis->setex(\Logic\Define\CacheKey::$perfix['findPass'].$user['id'], 5*60, $user['id']);

        return [
            'type'          => !empty($safeList) ? 1 : 2,
            'list'          => $safeList,
            'telphone_code' => (string)$user['telphone_code'] . \Utils\Utils::RSADecrypt($user['moblie']),
            // 'user_id' => $user['id']
        ];
    }
};