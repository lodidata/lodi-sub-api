<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "会员安全中心-修改真实姓名";
    const DESCRIPTION = "返回状态";
    const TAGS = "安全中心";
    const PARAMS = [
       "name" => "string(required) #真实姓名",
       //"number" => "string(required) #身份证号码"
   ];
    const SCHEMAS = [
   ];


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $validator = $this->validator->validate($this->request, [
            'name' => V::noWhitespace()->length(2, 20)->setName($this->lang->text("idcard name")),
            //'number' => V::idcard()->setName($this->lang->text("idcard number")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        /*$idcard = \Utils\Utils::RSAEncrypt($this->request->getParam('number'));
        // if (\Model\Profile::where('idcard', $idcard)->first() || \Model\Profile::where('user_id', $this->auth->getUserId())->where('idcard', '!=', 'null')->first()) {
        if (\Model\Profile::where('idcard', $idcard)->first()) {
            return $this->lang->set(110);
        }*/

        // $name = \Utils\Utils::RSAEncrypt($this->request->getParam('name'));
        
        $name = $this->request->getParam('name');
        try {
            $this->db->getConnection()->beginTransaction();
            \Model\Profile::where('user_id', $this->auth->getUserId())->update(['name' => $name]);
            \Model\SafeCenter::where('user_id', $this->auth->getUserId())->update(['id_card' => 1]);
            //$idcard = '****' . substr($this->request->getParam('number'), -4);
            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $this->logger->error($this->lang->text("ID card binding failed").':'.$e->getMessage(), ['uid' => $this->auth->getUserId(), 'name' => $this->request->getParam('name')]);
            return $this->lang->set(111);
        }

        if(empty(DB::table('user')->where('id',$user_id)->whereRaw("find_in_set('refuse_sale',auth_status)")->get()->toArray()))
            (new \Logic\Activity\Activity($this->ci))->bindIdCard($this->auth->getUserId());// 执行绑定身份证活动
        return$this->lang->set(0);
    }
};