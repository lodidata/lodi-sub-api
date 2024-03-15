<?php

use lib\validate\BaseValidate;
use Model\Label;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '回水查询列表';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {


        $params = $this->request->getParams();
        $label = new Label();
        $tags = $label->getIdByTags('试玩');
        $tags2 = $label->getIdByTags('测试');
        $query = \DB::table('rebet');
        if (isset($params['user_name']) && $params['user_name']) {
            $query->where('user_name',$params['user_name']);
        }
        if (isset($params['start_time']) && $params['start_time']) {
            $query->where('day','>=',$params['start_time']);
        }
        if (isset($params['end_time']) && $params['end_time']) {
            $query->where('day','<=',$params['end_time']);
        }
        if (isset($params['type']) && $params['type']) {
            $query->where('type','=',$params['type']);
        }//第三方平台 ID
        if (isset($params['plat_id']) && $params['plat_id']) {
            $query->where('plat_id','=',$params['plat_id']);
        }
        if(isset($params['hall_level'])) {
            if(in_array($params['hall_level'],[4,5])) {
                    $params['hall_level'] = 5;
            }
            $query->where('hall_level','=',$params['hall_level']);
        }

        if (isset($params['status']) && is_numeric($params['status'])) {
            $query->where('status','=',$params['status']);
        }

        $params['page'] = isset($params['page']) ? $params['page'] : 1;
        $params['page_size'] = isset($params['page_size']) ? $params['page_size'] : 10;
        $sumTotal = $query->count();
        $query->forPage($params['page'],$params['page_size'])->orderBy('id','DESC');
        $data = $query->get()->toArray();
        //得到彩种名称
        $lotteryData = DB::table('lottery')->get(['id','name']);
        $lotteryList = [];
        foreach ($lotteryData as $k => $v) {
            $v = (array)$v;
            $lotteryId = $v['id'];
            $lotteryList[$lotteryId] = $v;
        }
        $result = [];
        $result['lottery_list'] = $lotteryList;
        if ($data) {
            foreach ($data as $key => $value) {
                $data[$key] = (array)$data[$key];
                if ($value->hall_level == '4') {
                    $data[$key]['hall_level_old'] = $value->hall_level;
                    $data[$key]['hall_level'] = 5;
                    
                }
                $data[$key]['a_percent'] = intval($data[$key]['a_percent']);
            }
            $result['rebet_data'] = $data;
        }
        $attributes['total'] = $sumTotal;
        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];
        $attributes['rebet'] = 0.00;
        $attributes['win_money'] = 0.00;

        return $this->lang->set(0, [], $result, $attributes);


    }

};
