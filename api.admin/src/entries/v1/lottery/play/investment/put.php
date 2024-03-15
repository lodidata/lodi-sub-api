<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE = '彩票设定';
    const DESCRIPTION = '';

    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $params = $this->request->getParams();
        if (!isset($params['hall_id']) && !isset($params['lottery_id']))
            return $this->lang->set(10010);

        (new BaseValidate([
            'hall_id' => 'isPositiveInteger',
            'lottery_id' => 'isPositiveInteger',
//            'all_bet_max'=>'require|isPositiveInteger|gt:per_bet_max',
//            'per_bet_max'=>'require|isPositiveInteger|lt:all_bet_max',
//            'min_bet'=>'isPositiveInteger|lt:per_bet_max',
        ]))->paramsCheck('', $this->request, $this->response);

        $hall_name = [
            '-1' => '所有单期投注最大限额',
            '-2' => '个人单期投注最大限额',
            '1' => '[回水厅]投注最小限额(元)',
            '2' => '[保本厅]投注最小限额(元)',
            '3' => '[高赔率厅]投注最小限额(元)',
            '5' => '[传统]投注最小限额(元)',
        ];


        if (isset($params['lottery_id']) && !empty($params['lottery_id'])) {

            (new BaseValidate([
                'all_bet_max' => 'require|isPositiveInteger|gt:per_bet_max',
                'per_bet_max' => 'require|isPositiveInteger|lt:all_bet_max',
            ],
                ['all_bet_max.gt' => '所有投注总额必须大于个人投注限额', 'per_bet_max.lt' => '所有投注总额必须大于个人投注限额'],
                ['all_bet_max' => '所有投注总额', 'per_bet_max' => '个人投注限额']
            ))->paramsCheck('', $this->request, $this->response);

            $lottery = DB::table('lottery')->find($id);
            if (!$lottery)
                return $this->lang->set(10413);
            //判断个人单期下注限额是否小于大厅单期下注限额
            if (isset($params['hall_id'])) {
                $halls = DB::table('hall')->selectRaw('id,hall_name,hall_level,min_bet')
                    ->where('lottery_id', $params['lottery_id'])
                    ->where('id', $params['hall_id'])
                    ->get()->toArray();
            } else {
                $halls = DB::table('hall')->selectRaw('id,hall_name,hall_level,min_bet')
                    ->where('lottery_id', $params['lottery_id'])
                    ->get()->toArray();
            }

            foreach ($halls as $v) {
                if ($params['per_bet_max'] < $v->min_bet)
                    return $this->lang->set(10415);
            }

            $lotteryData['all_bet_max'] = $params['all_bet_max'];
            $lotteryData['per_bet_max'] = $params['per_bet_max'];

            $hall_info = (array)$halls[0];
            $lottery_obj = \Model\Admin\Lottery::find($id);
            foreach ($lotteryData as $key => $lotteryDatum) {
                $lottery_obj->$key = $lotteryDatum;
            }
            $lottery_obj->desc_desc = "彩种名称:{$lottery->name}/限额类型:{$hall_name[$hall_info['hall_level']]}/";
            $res = $lottery_obj->save();
        }

        if (isset($params['hall_id']) && !empty($params['hall_id'])) {

            (new BaseValidate([
                'per_bet_max' => 'require|isPositiveInteger|lt:all_bet_max',
                'max_bet' => 'isPositiveInteger|lt:per_bet_max',
                'min_bet' => 'isPositiveInteger|lt:max_bet',
            ],
                ['per_bet_max.lt' => '单期投注总额必须小于投注总额限制', 'max_bet.lt' => '单注最大限额必须小于单期投注总额', 'min_bet.lt' => '单注最小限额必须小于单注最大限额'],
                ['per_bet_max' => '单期投注总额','max_bet' => '单注最大限额', 'min_bet' => '单注最小限额']

            ))->paramsCheck('', $this->request, $this->response);


            $hall = DB::table('hall')->where('id', $params['hall_id'])->where('lottery_id', $id)->first();
            if (!$hall)
                return $this->lang->set(10414);
            try {
                $this->db->getConnection()->beginTransaction();
                if ($hall->hall_level == 5) {  // 传统模式修改  PC统一也修改
                    $res = DB::table('hall')->where('lottery_id', $id)->where('hall_level', 4)->update(['min_bet' => $params['min_bet']]);
                }
                $hall = \Model\Admin\Hall::find($params['hall_id']);
                $hall->desc_desc = "限额类型:{$hall_name[$hall['hall_level']]}/";
                $hall->min_bet = $params['min_bet'];
                isset($params['max_bet']) && $hall->max_bet = $params['max_bet'];
                $hall->save();

                $this->db->getConnection()->commit();
                return $this->lang->set(0);
            } catch (\Exception $e) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(-2);
            }
        }
        if ($res === false)
            return $this->lang->set(-2);

        return $this->lang->set(0);

    }

};
