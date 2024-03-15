<?php
/**
 * 回水统计展示
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use \Logic\Define\CacheKey;

return new class() extends BaseController
{
//    const STATE = \API::DRAFT;
    const TITLE = '报表-回水报表';
    const DESCRIPTION = '报表-回水报表';
    
    const QUERY = [
        'lottery_id' => 'integer() #彩种ID',
        'hall_id' => 'integer() #类型',
        'play_id' => 'integer() #类型',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
             [
                "id" => "int() #序号",
                "name" => "string() #玩法名称",
                "odds" => "float() #赔率",
                "max_odds" => "float() #最高赔率",
                "reward_radio" => "float() #返奖率",
            ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $params = $this->request->getParams();

        //排序字段
        $select_column= isset($params['select_column']) ? $params['select_column'] : 'id';
        switch ($select_column) {
            case 'return_money' :$select_column = 'rebet';break;
            default : $select_column = 'id';
        }

        //排序顺序（asc是顺序，desc是倒序）
        $select_order= isset($params['select_order']) ? $params['select_order'] : 'asc';

        date_default_timezone_set('PRC');
        $count_date_start= isset($params['count_date_start']) ? $params['count_date_start'] : date('Y-m-d');

        $count_date_end= isset($params['count_date_end']) ? $params['count_date_end'] :date('Y-m-d');

        $sql = DB::table('rebet');

        //类别（pc28等）
        if(isset($params['plat_subclass'])&&$params['plat_subclass']&&$params['plat_subclass']!='all'){
            $sql->where('type','=',$params['plat_subclass']);
        }

        //模式
        if(isset($params['hall_level'])&&$params['hall_level']&&$params['hall_level']!='all'){
            if($params['hall_level']==4||$params['hall_level']==5){
                $params['hall_level']=5;
            }
            $sql->where('hall_level','=',$params['hall_level']);
        }

        $data = $sql
            ->where('day','>=',$count_date_start)
            ->where('day','<=',$count_date_end)
            ->where('plat_id','=',0)
            ->select('id','day AS count_date','plat_id','hall_level',DB::raw('sum(bet_amount) as lottery_bet_money'),DB::raw('sum(win_money) as lottery_lose_earn_money'),'type AS plat_subclass',DB::raw('sum(rebet) as return_money'))
            ->orderBy($select_column,$select_order)
            ->groupBy('type','hall_level')
            ->get()
            ->toArray();

        $name=['pc28'=>'幸运28类','ssc'=>'时时彩类','sc'=>'赛车类','k3'=>'快3类','11x5'=>'11选5类','lhc'=>'六合彩','bjc'=>'百家彩'];

        $hall_level=['1'=>'回水厅','2'=>'保本厅','3'=>'高赔率厅','5'=>'传统模式','6'=>'直播'];

        foreach ($data as $key=>&$value) {
            $value = (array)$value;
            $value['plat_name'] = '彩票';
            $value['count_date'] = date('Y-m-d',strtotime($value['count_date']));
            $name_value=$name[$value['plat_subclass']];
            $data[$key]['plat_subclass_name'] = $name_value;
            $data[$key]['plat_pattern']=$hall_level[$value['hall_level']];
        }

        return $data;

    }
};
