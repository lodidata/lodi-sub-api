<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController {
//    const STATE = \API::DRAFT;
    const TITLE = '玩法限额查询';
    const DESCRIPTION = '玩法限额查询';
    
    const QUERY = [
        'lottery_id' => 'integer() #彩种ID',
        'hall_id' => 'integer() #类型',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
                "lottery_id" => "int() #序号",
                "hall_id" => "int() #玩法名称",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {

        (new BaseValidate([
            'lottery_id'  => 'require|isPositiveInteger',
            'hall_id'  => 'require|isPositiveInteger',
            'group'=>'require'
        ]))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
        list($hallIds,$hallLevels,$hall,$hall2) = $this->getHallInfo($params['lottery_id']);

        $hallId = (int) (isset($params['hall_id']) ? $params['hall_id'] : current($hallIds));
        $hallLevel = $hall2[$hallId]['hall_level'];
        if ($hallLevel == 4 || $hallLevel == 5) {
            $model = " model IN ('标准', '快捷')";
        } else if ($hallLevel == 6) {
            $model = " model IN ('直播')";
        } else {
            $model = " model IN ('聊天')";
        }
        $lottery_pid = DB::table('lottery')->where('id',$params['lottery_id'])->value('pid');
        $play = DB::table('lottery_play_struct')
            ->selectRaw('id,model,play_id,`group`,name,lottery_pid')
            ->where('lottery_pid',$lottery_pid)
            ->where('group',$params['group'])
            ->whereRaw("$model")
            ->get()
            ->toArray();
        foreach ($play as &$v){
            $v->list = DB::table('lottery_play_limit_odds')
                ->select(['*'])
                ->where('lottery_id',$params['lottery_id'])
                ->where('hall_id',$params['hall_id'])
                ->where('play_id',$v->play_id)
                ->get()->toArray();
        }
        return $play;
    }

    protected function getHallInfo($lottery_id){
        $data = DB::table('hall')->where('lottery_id',$lottery_id)->orderBy('hall_level','desc')->get()->toArray();

        $data = array_map('get_object_vars',$data);
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
        return [$hallIds,$hallLevels,$hall,$hall2];
    }
};
