<?php
/**
 * 会员等级-回水设置
 * @author Taylor 2019-01-14
 */
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '大厅设置-回水';
    const PARAMS = [
        'user_level_id' => 'integer(required) #会员层级id'
    ];
    const SCHEMAS = [
        [
            'rows' => [
                "name"  => "string() #菜单列表如彩票，捕鱼，棋牌，体育，真人，电子",
                "list"  => "string() #菜单对应的菜单类型及模式类型",
                "rebot_time" => "int #回水时间",
            ],
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        if(!isset($params['user_level_id'])){//会员层级id
            return $this->lang->set(10900);
        }
        /*//获取一级彩种
        $lottery = DB::table('lottery')->selectRaw('id,name,pid,alias')->where('pid', 0)->whereRaw("FIND_IN_SET('enabled',state)")->get()->toArray();

        $lotteryConfig = new \Logic\Set\SystemConfig($this->ci);
        $lotteryAlias = $lotteryConfig->lotteryConfig()['alias'];//获取彩票对应的别名

        //彩票对应的回水设置，层级-彩种-模式
        foreach ($lottery as &$v) {
            $v->lottery_type = $lotteryType = isset($lotteryAlias[$v->alias]) ? $lotteryAlias[$v->alias] : strtolower($v->alias);//获取彩票类型
            $firstLotteryId = DB::table('lottery')->whereRaw("FIND_IN_SET('enabled',state)")->where('pid', $v->id)->limit(1)->value('id');

            $v->halls = DB::table('hall')
                ->selectRaw('id,rebet_desc,hall_level,hall_name,rebet_ceiling,rebet_config,rebet_condition,rebot_way')
                ->where('hall_level', '<>', 4)->where('lottery_id', $firstLotteryId)->get()->toArray();//获取彩种不同hall_level模式类型对应的回水设置

            foreach ($v->halls as $l => $ve) {
                switch ($ve->hall_level) {
                    case 1:
                        $v->halls[$l]->hall_name = '回水厅';
                        break;
                    case 2:
                        $v->halls[$l]->hall_name = '保本厅';
                        break;
                    case 3:
                        $v->halls[$l]->hall_name = '高赔率厅';
                        break;
                    case 5:
                        $v->halls[$l]->hall_name = '传统模式';
                        break;
//                    case 6:
//                        $v->halls[$l]->hall_name = '直播';
//                        break;
                }
                $rebet_value = DB::table('rebet_config')->where('hall_id',$ve->id)
                    ->where('user_level_id', $params['user_level_id'])->get()->toArray();//层级对应的回水设置
                $v->halls[$l]->rebot_way = json_decode($ve->rebot_way, true);//回水方式
                $v->halls[$l]->rebet_condition = json_decode($ve->rebet_condition, true);//回水条件
                $v->halls[$l]->status_switch = !empty($rebet_value) ? $rebet_value[0]->status_switch : 0;//回水开关
                if(empty($rebet_value)){
                    $v->halls[$l]->rebot_way='';
                    $v->halls[$l]->rebet_condition='';
                    $v->halls[$l]->rebet_ceiling='';
                }else{
                    $v->halls[$l]->rebot_way = json_decode($rebet_value[0]->rebot_way, true);
                    $v->halls[$l]->rebet_condition = json_decode($rebet_value[0]->rebet_condition, true);
                    $v->halls[$l]->rebet_ceiling = $rebet_value[0]->rebet_ceiling;//回水上线
                }
            }
        }*/

        $rebetConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
        $rebetMultiple = $rebetConfig['day'][$params['user_level_id']] ?? 0;
        $gtZeroSwitch = $rebetConfig['day_gt_zero'][$params['user_level_id']] ?? false;

        //第三方菜单
        $menu_value = DB::table('game_menu')->selectRaw('id,name,type')->where('pid', 0)->where('type', '!=', 'CP')->where('switch', 'enabled')->orderBy('id', 'desc')->get()->toArray();

        $game_value = [];
        $i = 0;
        foreach ($menu_value as $val) {
            //查找二级菜单，二级菜单不存在时，不显示一级菜单
            $s_menu = DB::table('game_menu')->selectRaw('id,name,type')->where('pid', $val->id)->where('switch', 'enabled')->orderBy('id', 'desc')->get()->toArray();
            //只对二级菜单设置回水
            if(!empty($s_menu)){
                $game_value[$i]['name'] = $val->name;//一级菜单
                $list = [];
                $m = 0;
                foreach($s_menu as $gval){
                    $list[$m]['id'] = $gval->id;
                    $list[$m]['name'] = $gval->name;
                    $halls = DB::table('hall_3th')->selectRaw('id,game_id,rebet_desc,game_name,rebet_ceiling,rebet_condition,rebot_way')->where('game_id', $gval->id)->get()->toArray();//查询二级菜单游戏总的配置
                    if(!empty($halls)){
                        foreach($halls as $key=>&$valh){
                            if (!empty($valh->rebot_way)) {//有设置回水条件
                                $valh->rebot_way = json_decode($valh->rebot_way, true);
                                $valh->rebet_condition = json_decode($valh->rebet_condition, true);
                                $rebet_game = DB::table('rebet_config')->where('game3th_id', $valh->game_id)
                                    ->where('user_level_id', $params['user_level_id'])->get()->toArray();//该等级和游戏id对应的回水设置
                                $valh->status_switch = !empty($rebet_game) ? $rebet_game[0]->status_switch : 0;
                                $valh->rebet_multiple = $rebetMultiple;
                                $valh->rebet_gt_zero_switch = $gtZeroSwitch;
                                if(empty($rebet_game)){
                                    $valh->rebot_way='';//回水方式
                                    $valh->rebet_condition='';//回水条件
                                    $valh->rebet_ceiling='';//回水上限
                                }else{
                                    $valh->rebot_way = json_decode($rebet_game[0]->rebot_way,true);
                                    $valh->rebet_condition = json_decode($rebet_game[0]->rebet_condition,true);
                                    $valh->rebet_ceiling = $rebet_game[0]->rebet_ceiling;
                                }
                            }
                        }
                    }
                    $list[$m]['halls'] = $halls;
                    $m++;
                }
                $game_value[$i]['list'] = $list;//一级菜单回水列表
                $game_value[$i]['type'] = $val->type;//一级菜单类型
                $i++;
            }
        }

        //array_unshift($game_value, ['name' => '彩票', 'list' => $lottery, 'type' => 'CP']);
        $halls_value['alls'] = $game_value;
        $r_time = $this->redis->get(\Logic\Define\CacheKey::$perfix['rebot_time']);//从缓存中获取回水时间
        $rebot_time_minute = $this->redis->get(\Logic\Define\CacheKey::$perfix['rebot_time_minute']);
        $r_time = isset($r_time) ? $r_time : '5';
        $rebot_time_minute = isset($rebot_time_minute) ? $rebot_time_minute : '00';
        $halls_value['rebot_time'] = $r_time;
        $halls_value['rebot_time_minute'] = $rebot_time_minute;
        return $halls_value;
    }
};
