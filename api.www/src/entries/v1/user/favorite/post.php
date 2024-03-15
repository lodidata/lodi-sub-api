<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\Favorites;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "游戏收藏，取消收藏";
    const DESCRIPTION = "";
    const TAGS = "用户收藏";
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
        $game_id  = (int)$this->request->getParam('game_id');
        $status   = (int)$this->request->getParam('status');

        if($status){
            //查看记录是否存在
            $id  = Favorites::getId($userId, $game_id);
            if($id){
                return $this->lang->set(-1, []);
            }
            //收藏
            $res = Favorites::addFavorite($userId, $game_id);
        }else{
            //取消收藏
            $res = Favorites::delFavorite($userId, $game_id);
        }

        return $this->lang->set($res ? 0: -1, []);
    }
};