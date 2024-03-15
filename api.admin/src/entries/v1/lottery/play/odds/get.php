<?php

use Logic\Admin\BaseController;
use \lib\validate\BaseValidate;

return new class() extends BaseController {
//    const STATE = \API::DRAFT;
    const TITLE = '赔率查询';
    const DESCRIPTION = '赔率查询 - 接口重构版';
    
    const QUERY = [
        'lottery_id' => 'integer() #彩种ID',
        'hall_id'    => 'integer() #类型',
        'play_id'    => 'integer() #类型',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
            [
                "id"           => "int() #序号",
                "name"         => "string() #玩法名称",
                "odds"         => "float() #赔率",
                "max_odds"     => "float() #最高赔率",
                "reward_radio" => "float() #返奖率",
            ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        $req = $this->request->getParams();
        (new BaseValidate(
            [
                'lottery_id' => 'require',
                'hall_id'    => 'require',
                'group'      => 'require',
            ]
        ))->paramsCheck('', $this->request, $this->response);
        if (isset($req['group']) && !empty($req['group'])) {
            list($hallIds, $hallLevels, $hall, $hall2) = $this->getHallInfo($req['lottery_id']);

            $hallId = (int)(isset($req['hall_id']) ? $req['hall_id'] : current($hallIds));
            $hallLevel = $hall2[$hallId]['hall_level'];
            if ($hallLevel == 4 || $hallLevel == 5) {
                $model = " model IN ('标准', '快捷')";
            } else if ($hallLevel == 6) {
                $model = " model IN ('直播')";
            } else {
                $model = " model IN ('聊天')";
            }

            $lottery_pid = DB::table('lottery')
                ->where('id', $req['lottery_id'])
                ->value('pid');

            // 查询玩法结构
            //dd(DB::getQueryLog());exit;
            $play = DB::table('lottery_play_struct')
                ->selectRaw('id,model,play_id,`group`,name,lottery_pid')
                ->where('lottery_pid', $lottery_pid)
                ->where('group', $req['group'])
                ->whereRaw('open', 1)
                ->whereRaw("$model")
                ->get()
                ->toArray();

            foreach ($play as &$v) {
                $v->list = DB::table('lottery_play_odds')
                    ->select(DB::raw('id,name,odds,max_odds,FORMAT((odds/max_odds) * 100, 2) as reward_radio'))
                    ->where('lottery_id', $req['lottery_id'])
                    ->where('hall_id', $req['hall_id'])
                    ->where('play_id', $v->play_id)
                    ->orderBy('play_sub_id')
                    ->get()
                    ->toArray();
            }

            return $play;
        }
    }

    protected function getHallInfo($lottery_id) {
        $data = DB::table('hall')
            ->where('lottery_id', $lottery_id)
            ->orderBy('hall_level', 'desc')
            ->get()
            ->toArray();

        $data = array_map('get_object_vars', $data);
        $hall = [];
        $hallIds = [];
        $hallLevels = [];
        $hall2 = [];

        foreach ($data as $v) {
            $hallIds[] = $v['id'];
            $hallLevels[] = $v['hall_level'];
            $hall[] = ['id' => $v['id'], 'name' => $v['hall_name']];
            $hall2[$v['id']] = $v;
        }
        return [$hallIds, $hallLevels, $hall, $hall2];
    }
};
