<?php
use Utils\Www\Action;
use Utils\Utils;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "排行榜";
    const TAGS = "个人中心";
    const SCHEMAS = [
       [
           'money' => "string(required) #金额 100",
           'name'     => "string() #用户名称 a***b",
           'avatar' => "string(required) #头像",
           'level' => 'string(required) #会员等级 Level1'
       ]
    ];


    public function run() {
        if($result = $this->redis->get('ranking')){
            return $this->lang->set(
                0, [], json_decode($result, true)
            );
        }
        //100万到500万 10个
        //10到100万 40个
        $value = [];
        while(count($value) < 10){
            array_push($value, random_int(100*10000, 500*10000));
        }
        while(count($value) < 50){
            array_push($value, random_int(10*10000, 100*10000));
        }
        arsort($value);
        $value = array_values($value);
        $level = \DB::table('user_level')->select('level', 'deposit_money')->orderBy('level','DESC')->get()->toArray();
        $result = [];
        foreach ($value as $k => $v){
            $result[$k]['money'] = $v;
            $result[$k]['name']  = Utils::randUsername();
            $result[$k]['avatar']  = showImageUrl('/avatar/'.random_int(1,19).'.png');
            foreach ($level as $val){
                if($v > $val->deposit_money){
                    $result[$k]['level'] = $val->level;
                    break;
                }
            }
        }
        if($result){
            $this->redis->set('ranking', json_encode($result));
            $this->redis->expire('ranking', 24*3600);
        }
        return $this->lang->set(
            0, [], $result
        );
    }
};