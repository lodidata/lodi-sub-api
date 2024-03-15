<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人中心-个人资料-完善资料";
    const TAGS = "个人中心";
    const PARAMS = [
       "name"           => "string() #姓名",
       "avatar"         => "int() #头像id",
       "gender"         => "int() #性别 (1:男,2:女,3:保密)",
       "city"           => "int() #城市ID",
       "address"        => "string() #详细地址",
       "nationality"    => "int() #国籍",
       "birth_place"    => "int() #出生地",
       "birth"          => "string() #出生日期 2018-08-23",
       //"currency"       => "int() #货币",
       //"first_account"  => "string() #首选账户",
       "qq"             => "string() #qq",
       "wechat"         => "string() #wechat",
       "nickname"       => "string() #nickname昵称",
       "skype"          => "string() #skype",
       "mobile"         => "string() #mobile",
       "email"          => "string() #email",
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $info = \Model\Profile::where('user_id', $userId)->first();
        $truename = $info['name'];
        $profileParams = [
            'name' => $this->request->getParam('name', $info['name']),
            'nickname' => $this->request->getParam('nickname', $info['nickname']),
            'gender' => $this->request->getParam('gender', $info['gender']),
            'qq' => $this->request->getParam('qq', $info['qq']),
            'weixin' => $this->request->getParam('wechat', $info['weixin']),
            'skype' => $this->request->getParam('skype', $info['skype']),
            'region_id' => $this->request->getParam('city', $info['region_id']),
            'address' => $this->request->getParam('address', $info['address']),
            'nationality' => $this->request->getParam('nationality', $info['nationality']),
            'birth_place' => $this->request->getParam('birth_place', $info['birth_place']),
            'birth' => $this->request->getParam('birth', $info['birth']),
            'email' => $this->request->getParam('email', $info['email']),
            'mobile' => $this->request->getParam('mobile', $info['mobile']),
        ];

        //如果真实姓名存在 就不能再修改
        if ($truename) {
            unset($profileParams['name']);
        }
        

        if (!empty($info['avatar'])) {
            $profileParams = array_merge($profileParams, [
                'avatar' => $info['avatar'],
            ]);
        }

        //绑定邮箱
        if (empty($info['email'])) {
            \Model\SafeCenter::where('user_id', $userId)->update(['email' => 1]);
            $activity = new \Logic\Activity\Activity($this->ci);
            $activity->bindInfo($userId, 2);
        }
        $profileParams = \Utils\Utils::RSAPatch($profileParams);
        $profileParams = array_filter($profileParams);

        \Model\Profile::where('user_id', $userId)->update($profileParams);

        if (empty($info['mobile'])) {
            \Model\SafeCenter::where('user_id', $userId)
                ->update(['mobile' => 1]);
            $activity = new \Logic\Activity\Activity($this->ci);
            $activity->bindInfo($userId, 1);
        }
        if(isset($profileParams['mobile']) && empty($profileParams['mobile'])) {
            \Model\User::where('id', $userId)
                ->update(['mobile' => $profileParams['mobile'], 'telphone_code' => $this->request->getParam('telphone_code')]);
        }
        return $this->lang->set(0);
    }
};