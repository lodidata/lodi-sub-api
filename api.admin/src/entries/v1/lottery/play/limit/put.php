<?php
/**
 *
 * {"id": "113", "sort": 111, "tags": [{
 * "nm": "u5927u5c0fu5355u53cc",
 * "tp": ["1", "2", "3", "4"]
 * }]}
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use lib\exception\BaseException;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '更改赔率接口（新版）';
    const DESCRIPTION = '更改赔率接口';
    
    const PARAMS = [
        'id'           => 'string() # 赔率ID 传*时表示批量将这个玩法的全部修改',
        'reward_radio' => 'int() # 返奖率',
        'odds'         => 'int() # 赔率',
        'play_id'      => 'int() # 玩法ID',
        'hall_id'      => 'int() # 厅ID',
        'lottery_id'   => 'int() # 彩种ID',
        'lottery_pid'  => 'int() # 彩种父ID',
    ];
    

    public $id;

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];


    public function run() {

        (new BaseValidate([
            'id'          => 'isPositiveInteger',
            'play_id'     => 'isPositiveInteger',
            'hall_id'     => 'isPositiveInteger',
            'lottery_id'  => 'isPositiveInteger',
            'lottery_pid' => 'isPositiveInteger',
            'max_betting' => 'require|isPositiveInteger|egt:100',
        ], ['max_betting.egt' => '投注限额必须大于等于1'], ['max_betting' => '单期投注限额']
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();

        if (isset($params['id']) && !empty($params['id'])) {

            $result = DB::table('lottery_play_limit_odds')
                        ->find($params['id']);
            if (!$result) {
                return $this->lang->set(10015);
            }
        }

        $query = DB::table('lottery_play_limit_odds');

        $fields = ['id', 'play_id', 'hall_id', 'lottery_id', 'lottery_pid'];
        foreach ($fields as $field) {
            if (empty($params[$field])) {
                continue;
            }

            $query->where($field, $params[$field]);
        }


        $result = $query->update(['max_betting' => $params['max_betting']]);


        if ($result === false) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }

    protected function _removeCache($lotteryId, $hallId) {
        $redis = $this->redis;
        $redis->del('lottery_play_struct_' . $lotteryId . '_' . $hallId);
        $redis->incr('lottery_play_struct_ver_' . $lotteryId . '_' . $hallId);
    }
};