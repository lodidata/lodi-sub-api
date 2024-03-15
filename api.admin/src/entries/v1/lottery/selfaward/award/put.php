<?php
/**
 * Created by PhpStorm.
 * User: liluming
 * Date: 2017/11/2
 * Time: 15:08
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE = '修改开奖号码';
    const DESCRIPTION = '修改自开板块可输入号码开奖';
    
    const PARAMS = [
        'lottery_id' => 'int(required) #自开型彩种ID',
        'number_id' => 'int() #彩期',
        'type' => 'string() #彩种类型',
        'codes' => 'string() #开奖号码',
        'model' => 'int() #开奖模式1随机开奖2盈利最大3盈亏范围',
        'start' => 'float() #开始',
        'end' => 'float() #结束',
    ];
    const SCHEMAS = [
        [
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        (new BaseValidate([
            ['lottery_id', 'require|isPositiveInteger'],
            ['codes', 'require'],
            ['number_id', 'requireWith:codes|isPositiveInteger'],
        ],
            [],
            ['lottery_id' => '彩票ID', 'number_id' => '彩期']
        ))->paramsCheck('', $this->request, $this->response);

        $req = $this->request->getParams();
        $lottery_id = $req['lottery_id'];
        $number_id = $req['number_id'];
        $type = $req['type'];
        $model = $req['model'];
        $period_type = 'manual_lottery';//手动开奖
        $codes = $req['codes'];


        if ($lottery_id == 106) {
            $codes2 = explode(',', $codes);
            $temp = array_count_values($codes2);
            if (count($temp) != 10) {
                return $this->lang->set(9001);
            }

            foreach ($codes2 as $key => $value) {
                if (!in_array($value, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])) {
                    return $this->lang->set(9001);
                }
                $value = intval($value);
                $codes2[$key] = $value < 10 ? '0' . $value : $value;
            }
            $codes = join(',', $codes2);
        }

        if (empty($req['codes'])) {//为空

            $result = $this->updateOpenPrize($lottery_id, $req);

        } else {
            //系统开奖设置
            if ($model == 1) {//随机开奖
                $period_type = 'rand_lottery';
            } elseif ($model == 2) {//派奖最少也就是盈利最大
                $period_type = 'reward_the_least';
            } elseif ($model == 3) {//盈亏范围
                $period_type = 'interval_lottery';
            }

            $result = $this->updatePrizeResult($number_id, $lottery_id, $type, $period_type, $codes);
        }
        return $result;


    }

    /**
     * @param string $lottery_id
     * @param array $params
     * @return bool
     */
    public function updateOpenPrize($lottery_id = '', $params = [])
    {

        $data = [

            'interval_start' => $params['start'] ? $params['start'] / 100 : 0,
            'interval_end' => $params['end'] ? $params['end'] / 100 : 0,
            'period_code_type' => empty($params['start']) && empty($params['start']) ? 'rand' : 'interval'
        ];

        $result = DB::table('openprize_config')->where('lottery_id', $lottery_id)->update($data);
        $info = DB::table('lottery')
            ->find($lottery_id);
        $sta = $result === false ? 0 : 1;
        if ($result !== false) {
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', $sta, "彩种名称:{$info->name}/期号：{$params['number_id']}/开奖号码：{$params['codes']}");
            return $this->lang->set(0);
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', $sta, "彩种名称:{$info->name}/期号：{$params['number_id']}/开奖号码：{$params['codes']}");
        return $this->lang->set(-2);

    }

    /**
     * 修改开奖号码
     * @param int $number_id
     * @param int $lottery_id
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function updatePrizeResult($number_id, $lottery_id, $type, $period_type, $codes)
    {

        if (!$number_id || !$type || !$codes) {
            return $this->lang->set(10010);
        }
        $table = "open_lottery_{$type}_{$lottery_id}";
        $res = DB::table("$table")->select(['start_time', 'end_time','period_code'])->where('lottery_number', $number_id)->first();
        $res = (array)$res;
        $now = time();

        $info = DB::table('lottery')
            ->find($lottery_id);

        if($res['period_code']!=''){
            return $this->lang->set(886, ['已开奖，不能修改！']);
        }

        if (($res['start_time'] < $now && $now <= ($res['end_time'] + 19)) || ($now <= $res['end_time'] + 19)) {

            $res = DB::table("$table")->where('lottery_number', $number_id)->update(['period_type' => $period_type, 'period_code' => $codes]);

            $sta = $res === false ? 0 : 1;
            if ($res !== false) {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', $sta, "彩种名称:{$info->name}/期号：{$number_id}/开奖号码：{$codes}");
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', $sta, "彩种名称:{$info->name}/期号：{$number_id}/开奖号码：{$codes}");
            return $this->lang->set(-2);

        } else {
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', 0, "彩种名称:{$info->name}/期号：{$number_id}/开奖号码：{$codes}");
            $s = abs(time() - $res['end_time'] - 19);
            return $this->lang->set(9002, [$s]);
        }
    }
};