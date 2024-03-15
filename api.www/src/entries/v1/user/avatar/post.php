<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "更换头像";
    const TAGS = "会员头像";
    const PARAMS = [
       "id" => "int() #图片id"
   ];
    const SCHEMAS = [
   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $id = $this->request->getParam('id');
        if (empty($id)) {
            return $this->lang->set(10);
        }

        \Model\Profile::where('user_id', $this->auth->getUserId())->update(['avatar' => $id]);
        return [];
    }
};