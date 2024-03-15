<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

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

        (new BaseValidate([
            'lottery_id' => 'require|isPositiveInteger',
        ]))->paramsCheck('', $this->request, $this->response);

        $req = $this->request->getParams();

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


        if (isset($req['hall_id']) && !empty($req['hall_id'])) {

            $data = DB::table('hall')
                ->where('lottery_id', $req['lottery_id'])
                ->where('hall_level', '!=',4)   //PC与传统一起
                ->orderBy('hall_level', 'desc')
                ->get()
                ->toArray();

            $hallInfo = array_map('get_object_vars', $data);
            $hall = [];
            $hallIds = [];
            $hallLevels = [];
            $hall2 = [];

            foreach ($hallInfo as $v) {
                $hallIds[] = $v['id'];
                $hallLevels[] = $v['hall_level'];
                $hall[] = ['id' => $v['id'], 'name' => $v['hall_name']];
                $hall2[$v['id']] = $v;
            }

            $hallId = (int)(isset($req['hall_id']) ? $req['hall_id'] : current($hallIds));

            if (!isset($hall2[$hallId]['hall_level'])) {
                return $this->lang->set(10414);
            }

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
            $group = DB::table('lottery_play_struct')
                ->select(DB::raw('distinct(`group`)'))
                ->where('lottery_pid', $lottery_pid)
                ->whereRaw("$model")
                ->whereRaw('open', 1)
                ->get()
                ->pluck('group')
                ->toArray();

            return ['hall' => $hallInfo, 'group' => $group, 'play' => []];
        }

        if (isset($req['lottery_id']) && !empty($req['lottery_id'])) {
            $data = DB::table('hall')
                ->selectRaw('id,hall_name,lottery_id,hall_level')
                ->where('lottery_id', $req['lottery_id'])
                ->where('hall_level', '!=',4)  //PC与传统一起
                ->orderBy('hall_level', 'desc')
                ->get()
                ->toArray();
            return ['hall' => $data, 'group' => [], 'play' => []];
        }
    }

    protected function getHallInfo($lottery_id) {
        $data = DB::table('hall')
            ->where('lottery_id', $lottery_id)
            ->where('hall_level', '!=',4)  //PC与传统一起
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
