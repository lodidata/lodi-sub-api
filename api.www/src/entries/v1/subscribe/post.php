<?php

use Utils\Www\Action;
use Respect\Validation\Validator as Validator;
use Model\Subscribe as SubscribeModel;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '订阅';
    const DESCRIPTION = "返回状态 订阅成功";
    const TAGS = '公共分类';
    const PARAMS = [
        'telphone_code' => 'string(required) #区号',
        'telphone'      => 'string(required) #用户手机号码',
        'code'          => 'string(required) #手机验证码',
    ];
    const SCHEMAS = [
    ];

    public function run() {
        //$telphone_code  = trim($this->request->getParam('telphone_code'));
        $telphone       = trim($this->request->getParam('telphone'));

        $validator = $this->validator->validate($this->request, [
            'telphone'      => Validator::mobile()
                ->setName($this->lang->text("telphone")),
            'code'          => Validator::captchaTextCode()
                ->setName($this->lang->text("captcha code")),
        ]);
        /*if ($telphone_code != '+86') {

        } else {
            $validator = $this->validator->validate($this->request, [
                'telphone_code' => Validator::telephoneCode()
                                            ->setName($this->lang->text("telphone code")),
                'telphone'      => Validator::chinaMobile()
                                            ->setName($this->lang->text("telphone")),
                'code'          => Validator::captchaTextCode()
                                            ->setName($this->lang->text("captcha code")),
            ]);
        }*/

        if (!$validator->isValid()) {
            return $validator;
        }

        //手机号已订阅
        if(SubscribeModel::isSubscribe($telphone)){
            return $this->lang->set(4000);
        }

        $captcha        = new \Logic\Captcha\Captcha($this->ci);

        //验证手机短信
        if (!$captcha->validateTextCode($telphone, $this->request->getParam('code'))) {
            return $this->lang->set(106);
        }

        if(!SubscribeModel::insertMobile($telphone)){
            return $this->lang->set(4001);
        }
        return $this->lang->set(0);
    }
};