<?php

use Utils\Www\Action;

return new class extends Action {
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "第三方游戏列表";
    const TAGS = '游戏';
    const QUERY = [
        "play_id" => "int(required) #游戏菜单ID",
        "game_id" => "int(required) #第三方对应的具体小游戏的id",
    ];
    const SCHEMAS = [
        'status'    => "int(required) #状态 0开启",
        'url'       => "string(required) #游戏跳转链接",
        "isBrowser" => "boolean() #默认浏览器，因为android可能不支持的游戏"
    ];

    public function run() {
        $allow = \Logic\Set\SystemConfig::getModuleSystemConfig('gameCon');

        if(!isset($allow['allow_in']) || $allow['allow_in'] != 1) {
            return $this->lang->set(3014);
        }

        $data=(new \Logic\Set\SystemConfig($this->ci))->getStartGlobal();

        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $u_tags = \Model\User::where('id',$uid)->value('tags');
        if(in_array($u_tags, [4,7])) {
            return $this->lang->set(3013);
        }

        $play_id = $this->request->getParam('play_id');
        $game_id = $this->request->getParam('game_id');
        $play_games = \Model\Game3th::where('id', $play_id)->first();
        if (!$play_games && !$game_id) {
            return $this->lang->set(3010);
        }
        $play_games = $play_games ? $play_games->toArray() : [];
        if (isset($play_games['maintain']) && $play_games['maintain'] == 'maintenance') {
            return $this->lang->set(3012);
        }
        $game_id = $game_id ? $game_id : $play_games['game_id'];
        try {
            $games = (array)DB::table('game_menu')
                ->where('id', $game_id)
                ->where('switch', "=", 'enabled')
//                ->whereRaw('m_end_time < NOW()')
//                ->whereRaw('m_end_time < NOW()')
                ->first();//var_dump($games);die;
            // 游戏维护中
            $time = date('Y-m-d H:i:s');
            if (count($games) == 0 || $games['m_end_time'] <= $time && $games['m_start_time'] >= $time) {
                return $this->lang->set(3012,[$games['m_start_time'],$games['m_end_time']]);
            }
            $quota=DB::table('quota')
                ->select('surplus_quota')
                ->get()
                ->first();
            if($quota){
                if($quota->surplus_quota<=0){
                    return $this->lang->set(886,[$this->lang->text("The third party exchange quota has been used up, please contact the administrator!")]);
                }
            }
            $gameClass = \Logic\GameApi\GameApi::getApi($games['type'], 0);
            $game_list = $gameClass->GetListGame();

            return $this->lang->set(0, [],$game_list);

        } catch (\Exception $e) {
            return $this->lang->set(3011);
        }
    }
};