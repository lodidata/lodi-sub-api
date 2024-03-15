<?php
use Utils\Www\Action;
use Model\MessagePub;
use  Logic\Define\Lang;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "提交反馈";
    const DESCRIPTION = "提交反馈";
    const TAGS = "提交反馈";
    const PARAMS = [
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        //图形验证码
        $validator = $this->validator->validate($this->request, [
            'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
            'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);
        if (!$validator->isValid()) {
            return $validator;
        }
        $code   = $this->request->getParam('code');
        $token  = $this->request->getParam('token');
        $result = (new \Logic\Captcha\Captcha($this->ci))->validateImageCode($token, $code);
        if(empty($result)) return $this->lang->set(10045);

        $type = $this->request->getParam('type',0);
        $question = $this->request->getParam('question', '');
        $mobile = $this->request->getParam('mobile', '');
        $img = $this->request->getParam('img', '');
        //设置频率限制redis_key
        $lock_key       = "feedback_code".$this->auth->getUserId();
        $lock_code_key  = "user_feedback_pending".$this->auth->getUserId();
        //获取redis Key
        $lock           = $this->redis->get($lock_key); //接口请求限制10秒
        $pending_lock   = $this->redis->get($lock_code_key); //提交成功限制5分钟
        //提交成功限制5分钟
        if($pending_lock){
            return $this->lang->set(905);
        }
        //接口请求限制10秒
        if($lock){
            return $this->lang->set(886,['please wait a moment!']);
        }
        $this->redis->setex($lock_key,10,1);

        if (!is_numeric($type) || $type <= 0) {
            return $this->lang->set(10);
        }
        if(mb_strlen($question) < 15){
            return $this->lang->set(4009);
        }
        if(mb_strlen($question) > 3000){
            return $this->lang->set(4010);
        }
        $question = htmlspecialchars($question);
        if (empty($mobile)) {
            return $this->lang->set(10);
        }
        $mobile = \Utils\Utils::RSAEncrypt($mobile);

        //验证完毕 添加数据
        $userId =$this->auth->getUserId();
        $username = \Model\User::where('id',$userId)->value('name');
        $origins = ['pc'=>1,'h5'=>2,'ios'=>3,'android'=>4];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $add = [
            'user_id'   => $userId,
            'user_name' => $username,
            'mobile'    => $mobile,
            'type'      => $type,
            'question'  => $question,
            'img'       => replaceImageUrl($img),
            'origin'    => isset($origins[$origin]) ? $origins[$origin] : 0,
            'status'    => 0,
            'reply'     => '',
        ];
        $res = DB::table('user_feedback')->insert($add);
        if(!$res){
            $this->redis->del($lock_key);
            return $this->lang->set(-2);
        }
        $this->redis->setex($lock_code_key,60*5,1);
        return $this->lang->set(0);

    }
};