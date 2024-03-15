<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '盈亏返佣比例配置';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const STATEs      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        //获取所有游戏类型
        $game_menu = DB::table('game_menu')->where('pid',0)->where('type','!=','CP')->select('type','rename')->get()->toArray();

        //获取参与比例配置
        $profit_ratio = DB::table('system_config')->where('module','profit_loss')->where('key','profit_ratio')->where('state','enabled')->first();
        $ratio_list = [];
        if(!empty($profit_ratio) && !empty($profit_ratio->value)){
            $ratio_list = json_decode($profit_ratio->value, true);
        }

        $data = [];
        if(!empty($game_menu)){
            foreach($game_menu as $key => $val){
                $val = (array)$val;
                $is_take = 1;//是否参与结算 1是 0否
                $ratio = 100;
                if(isset($ratio_list['defalut'])){
                    $is_take = $ratio_list['defalut']['is_take'];
                    $ratio = $ratio_list['defalut']['ratio'];
                }
                if(isset($ratio_list[$val['type']])){
                    $is_take = $ratio_list[$val['type']]['is_take'];
                    $ratio = $ratio_list[$val['type']]['ratio'];
                }
                $data[] = [
                    'type'    => $val['type'],
                    'name'    => $this->lang->text($val['type']),
                    'ratio'   => $ratio,
                    'is_take' => $is_take,
                ];
            }
        }
        $attributes = [];

        return $this->lang->set(0, [], $data, $attributes);
    }
};
