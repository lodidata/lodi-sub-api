<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\Favorites;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "用户收藏列表";
    const TAGS = "用户收藏";
    const DESCRIPTION = "";
    const PARAMS = [
       "game_id"    => "int(required) #游戏id",
       "status"     => "int(required) #1:收藏，0:取消收藏",
   ];
    const SCHEMAS = [
   ];

    
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId   = $this->auth->getUserId();

        $res = Favorites::userFavoriteList($userId);
        $new_res = [];
        foreach ($res ?? [] as $v){
            $v['name'] = $this->lang->text($v['type']);
            $new_res[] = $v;
        }

        return $new_res;
    }
};