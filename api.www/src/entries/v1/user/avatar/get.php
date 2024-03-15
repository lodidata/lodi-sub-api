<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取头像";
    const TAGS = "会员头像";
    const SCHEMAS = [
           "id" => "int() #id",
           "url" => "string() #头像图片地址",
           "check" => "int() #当前头像（1：是，0：不是）"
   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $profile = \Model\Profile::where('user_id', $this->auth->getUserId())->first();
        return $this->avatar($profile['avatar']);
    }

    /**
     * 头像
     */
    protected function avatar($id = 1) {
        $i = 1;
        $avatar  = [];

        while($i < 20){
            $avatar[] = [
                "id" => $i,
                "url" => showImageUrl("/avatar/{$i}.png"),
                "check" => 0,
            ];
            $i++;
        }


        foreach ($avatar as $k => $val) {
            if ($val['id'] == $id) {
                $avatar[$k]['check'] = 1;
                break;
            }
        }
        return $avatar;
    }
};