<?php
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE = \API::DRAFT;
    const TITLE = '玩法设定接口';
    const DESCRIPTION = '玩法设定接口 - 接口重构版';
    
    const QUERY = [
        'pid' => 'string() #分类ID',
        'model' => 'string() #类型',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
            [
                "id" => "int() #序号",
                "play_id" => "int() #玩法ID",
                "group" => "string() #玩法组",
                "name" => "string() #玩法名称",
                "open" => "int #启用 1启用 0不启用",
                "tags" => [
                    [
                        'nm' => 'string #名称',
                        'sv' => 'array #订单提交值',
                        'vv' => 'array #前端显示值',
                        'tp' => 'array #提示语' 
                    ]
                ],
                "play_text1" => "string() #提示语1",
                "play_text2" => "string() #提示语2",
            ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {

        $req = $this->request->getParams();
        $pid = (int) (isset($req['pid']) ? $req['pid'] : 1);
        //$models = ['','标准','快捷','聊天','直播'];
        $models = ['','标准'];
        $cate = [
            //0 => ['pid' => '1', 'name' => '幸运28类'],
           // 1 => ['pid' => '5', 'name' => '快3类'],
            2 => ['pid' => '10', 'name' => '时时彩类'],
            //3 => ['pid' => '24', 'name' => '11选5类'],
           // 4 => ['pid' => '39', 'name' => 'pk10类'],
           // 5 => ['pid' => '51', 'name' => '六合彩'],
        ];
        $model = [];

        $data = DB::table('lottery_play_struct')
                    ->select(['model'])
                    ->where('lottery_pid',$pid)
                    ->groupBy('model')
                    ->get()->toArray();
        $data = array_map('get_object_vars',$data);
        foreach ($data as $val) {
            $model[] = $val['model'];
        }

        $query = DB::table('lottery_play_struct')
            ->select(['id','play_id','model','group','buy_ball_num','is_ball_num',DB::raw("concat(`group`,'-',name) as name"),'sort','open','play_text1','play_text2','tags'])
            ->where('lottery_pid',$pid)
            ;

        $query = isset($req['model']) && !empty($req['model']) ? $query->where('model',$models[$req['model']]) : $query ;

            $data = $query->orderBy('sort','desc')
            ->get()->toArray();
//        dd(DB::getQueryLog());exit;
        $data = array_map('get_object_vars',$data);

        foreach ($data as $k => $v) {
            $data[$k]['tags'] = json_decode($v['tags'], true);
        }
        return ['cate' => $cate, 'model' => $model, 'list' => $data];
    }
};
