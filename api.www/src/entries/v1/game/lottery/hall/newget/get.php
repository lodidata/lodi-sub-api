<?php

use Respect\Validation\Validator as v;
use Utils\Www\Action;
use Model\Hall;
use Model\Room;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "根据彩种获取厅信息";
    const DESCRIPTION = "接口";
    const TAGS = "彩票";
    const QUERY = [
        "lottery_id" => "int(required) #对应彩票id，比如：时时彩、11选5、快三...，见彩种列表接口",
        "id" => "int #"
    ];
    const SCHEMAS = [
        [
            "id" => "int(option) # id",
            "hall_name" => "string(option) # 房间名称",
            "rebet_desc" => "string(option) # 回水描述"
        ]
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user = new \Logic\User\User($this->ci);
        $userId = $this->auth->getUserId();
        $info = $user->getInfo($userId);
        $level_id = \DB::table('user_level')->where('level',$info['ranting'])->value('id');
        //依据用户层级查询相应的数据
        //查询参数
        $type = $this->request->getParam('select_type');
        $lottery_type = $this->request->getParam('lottery_type');
        $hall_model = $this->request->getParam('hall_model');
        $game_name = $this->request->getParam('game_name');
        //判断是pc还是移动端  移动端：h5；pc：pc
        $pl = isset($this->request->getHeaders()['HTTP_PL']) ? $this->request->getHeaders()['HTTP_PL'][0] : null;
        $hall_model = $pl == 'pc' ? 5 : $hall_model;
        if($type == 'lottery') {
            $query = \DB::table('hall AS a')->leftJoin('rebet_config AS b','a.id','=','b.hall_id');
            $lottery_type && $query->where('type',$lottery_type);
            $hall_model && $query->where('a.hall_level', '=', $hall_model);
            $query->where('b.user_level_id',$level_id)->groupBy('type');
            $arr = ['a.id', 'a.hall_name', 'a.type  as name', 'b.rebet_desc', 'b.rebet_condition', 'b.rebot_way'];
        }else {
            $query = \DB::table('hall_3th AS a')->leftJoin('rebet_config AS b','a.game_id','=','b.game3th_id');
            $type && $query->where('3th_name',$type);
            $arr = ['a.id', 'a.game_name as name', 'a.3th_name as type', 'b.rebet_desc', 'b.rebet_condition', 'b.rebot_way'];
            $game_name && $query->where('game_name', '=', $game_name);
            $query->where('b.user_level_id',$level_id);
        }
        $query->where('b.status_switch',1);
        $data = $query->get($arr)->toArray();
        $res = [];
        foreach ($data as $v) {
            $v = (array)$v;
            if($v['name']) {
                $res[] = $v;
            }
        }
        if(!$res)
            return [];
        $re = $pl == 'pc' ? $res : current($res);
        $re['rebet_time'] = isset(\Logic\Define\CacheKey::$perfix['rebot_time']) ? $this->redis->get(\Logic\Define\CacheKey::$perfix['rebot_time']) : null;
//        print_r(DB::getQueryLog());
        return $re;
    }
};