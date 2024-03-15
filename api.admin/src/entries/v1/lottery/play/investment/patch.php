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



    const PARAMS = [
        'hall_level' => 'int(require) #厅层级，-1:所有单期投注最大限额，-2:个人单期投注最大限额',
        'bet_value' => 'int(require) #投注限额',
    ];

    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null)
    {
        $params = $this->request->getParams();

        if (!is_numeric($id)) {
            return $this->lang->set(10413);
        }

        $id = intval($id);
        $lottery = new stdClass();
        $lottery->name = '所有';
        if ($id != 0) {
            $lottery = DB::table('lottery')
                ->find($id);

            if (!$lottery) {
                return $this->lang->set(10413);
            }
        }

        $validate = new BaseValidate([
            'hall_level' => 'require|integer',
            'bet_value' => 'require|float',
        ], [
            'hall_level' => '请选择一个限额类型',
            'bet_value' => '请输入正确限额数值'
        ]);

        $validate->paramsCheck('patch', $this->request, $this->response);

        $level = (int)$params['hall_level'];

        $lottery_ids = false;
        $hall_name = [
            '-1' => '所有单期投注最大限额',
            '-2' => '个人单期投注最大限额',
            '1' => '[回水厅]投注最小限额(元)',
            '2' => '[保本厅]投注最小限额(元)',
            '3' => '[高赔率厅]投注最小限额(元)',
            '4' => '[PC房]投注最小限额(元)',
            '5' => '[传统]投注最小限额(元)',
        ];

        if ($id != 0) {
            $lottery_ids = DB::table('lottery')
                ->selectRaw('id')
                ->where('pid', $id)
                ->get()
                ->toArray();

            $lottery_ids = array_map(function ($row) {
                return $row->id;
            }, $lottery_ids);

            $lottery_ids = array_merge([
                $id,
            ], $lottery_ids);
        }

        //-1 代表修改所有单期投注最大限额(元)
        if ($level == -1) {
            $query = DB::table('lottery');

            if ($lottery_ids) {
                $query->whereIn('id', $lottery_ids);
            }

            $per_bet_max = $query->max('per_bet_max');

            //判断所有单期投注最大限额是否小于个人单期投注最大限额
            if ($params['bet_value'] <= $per_bet_max) {
                return $this->lang->set(10415);
            }

            $query = DB::table('lottery');

            if ($lottery_ids) {
                $query->whereIn('id', $lottery_ids);
            }

            $result = $query->update([
                'all_bet_max' => $params['bet_value'],
            ]);

            $sta = $result !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法限额', '彩种限额', '批量设置', $sta, "彩种类型:{$lottery->name}/限额类型:{$hall_name[$params['hall_level']]}/{$hall_name[$params['hall_level']]}(元)设置为：" . ($params['bet_value'] / 100));
            if ($result === false) {
                return $this->lang->set(-2);
            }

            return $this->lang->set(0);
        }

        //-2 代表修改个人单期投注最大限额(元)
        if ($level == -2) {
            $query = DB::table('lottery');

            if ($lottery_ids) {
                $query->whereIn('id', $lottery_ids);
            }

            $all_bet_max = $query->min('all_bet_max');

            //判断个人单期投注最大限额是否大于所有单期投注最大限额
            if ($params['bet_value'] >= $all_bet_max) {
                return $this->lang->set(10415);
            }

            $query = DB::table('lottery');

            if ($lottery_ids) {
                $query->whereIn('id', $lottery_ids);
            }

            $result = $query->update([
                'per_bet_max' => $params['bet_value'],
            ]);

            $sta = $result !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法限额', '彩种限额', '批量设置', $sta, "彩种类型:{$lottery->name}/限额类型:{$hall_name[$params['hall_level']]}/{$hall_name[$params['hall_level']]}(元)设置为：" . ($params['bet_value'] / 100));
            if ($result === false) {
                return $this->lang->set(-2);
            }

            return $this->lang->set(0);
        }

        //判断个人单期下注限额是否大于大厅单期下注限额
        $query = DB::table('lottery');

        if ($lottery_ids) {
            $query->whereIn('id', $lottery_ids);
        }

        $per_bet_max = $query->min('per_bet_max');

        if ($params['bet_value'] > $per_bet_max) {
            return $this->lang->set(10412);
        }

        $query = DB::table('hall');

        if ($lottery_ids) {
            $query->whereIn('lottery_id', $lottery_ids);
        }
        try {
            $this->db->getConnection()->beginTransaction();
            if ($params['hall_level'] == 5) { //传统和PC统一
                $pcUpdate = clone $query;
                $pcUpdate->where('hall_level', 4)
                    ->update([
                        'min_bet' => $params['bet_value'],
                    ]);
            }
            $result = $query->where('hall_level', $params['hall_level'])
                ->update([
                    'min_bet' => $params['bet_value'],
                ]);
            $this->db->getConnection()->commit();

            $sta = $result !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法限额', '彩种限额', '批量设置', $sta, "彩种类型:{$lottery->name}/限额类型:{$hall_name[$params['hall_level']]}/{$hall_name[$params['hall_level']]}(元)设置为：" . ($params['bet_value'] / 100));
            return $this->lang->set(0);
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $sta = $result !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法限额', '彩种限额', '批量设置', $sta, "彩种类型:{$lottery->name}/限额类型:{$hall_name[$params['hall_level']]}/{$hall_name[$params['hall_level']]}(元)设置为：" . ($params['bet_value'] / 100));
            return $this->lang->set(-2);
        }

    }
};
