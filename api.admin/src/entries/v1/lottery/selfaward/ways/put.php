<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/28 16:41
 */

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = "";
    const TITLE = '自开奖开奖概率控制设制';
    const DESCRIPTION = '';

    const QUERY = [
        'lottery_id' => 'int(require) #彩种ID',
        'type' => 'enum(1,2,3) #系统自开奖类型:1-返奖率开奖，2-奖池模式开奖,3-完全随机开奖',
        'jackpot' => 'int() #厅主给的奖池开奖基础金额（分）  --  type为2必须有',
        'max_profit' => 'int() #最大返奖率',
        'min_profit' => 'int() #最小返奖率',
        'interval_profit' => 'int() #返奖率控制率',
    ];
    
    const PARAMS = [];
    const STATEs = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $type = $this->request->getParam('type');
        $logs = [
            'opt_id' => $this->playLoad['uid'],
            'opt_name' => $this->playLoad['nick'],
        ];
        $req = $this->request->getParams();
        $lotteryId = $req['lottery_id'];
        if ($type == 2) {
            (new \lib\validate\BaseValidate([
                'lottery_id' => 'require|>=:0',
                'jackpot' => 'require|>=:0',
            ],
                [], ['lottery_id' => '彩种ID', 'jackpot' => '奖池基础金额']
            ))->paramsCheck('', $this->request, $this->response);
            $jackpot = $req['jackpot'];
            $result=\DB::table('openprize_config')->where('lottery_id', $lotteryId)->update(['jackpot' => $jackpot, 'period_code_type' => 'jackpot', 'jackpot_set' => '1']);
            $logs['opt_desc'] = '奖池开奖并重置奖池为' . ($jackpot / 100) . '元';

            $str="/奖池设置：". ($jackpot / 100) ;
        } elseif($type == 1) {
            (new \lib\validate\BaseValidate([
                'lottery_id' => 'require|>=:0',
                'max_profit' => 'require|>=:0',
                'min_profit' => 'require|>=:0',
                'interval_profit' => 'require|>=:0',
            ],
                [], ['lottery_id' => '彩种ID', 'interval_profit' => '控制期数', 'max_profit' => '最大返奖率', 'min_profit' => '最小返奖率']
            ))->paramsCheck('', $this->request, $this->response);

//            if ($req['max_profit'] - $req['min_profit'] < 0.2) {
            if ($req['max_profit'] - $req['min_profit'] < 0) {
                return $this->lang->set(10571);
            }
            $maxProfit = $req['max_profit'];
            $minProfit = $req['min_profit'];
            $interval_profit = $req['interval_profit'];
            $result=\DB::table('openprize_config')->where('lottery_id', $lotteryId)
                ->update(['min_profit' => $minProfit,
                    'max_profit' => $maxProfit,
                    'period_code_type' => 'interval',
                    'interval_profit'=>$interval_profit]);
            $logs['opt_desc'] = '返奖率开奖设制返奖率为' . ($minProfit * 100) . '% -- ' . ($maxProfit * 100) . '%';

            $str="/返奖率[". ($minProfit * 100)."%--". ($maxProfit * 100) ."%]";
        }else{
            $result=\DB::table('openprize_config')->where('lottery_id', $lotteryId)->update(['period_code_type' => 'rand']);
            $logs['opt_desc'] = '随机开奖' ;
            $str = '完全随机自开奖' ;
        }
        $res = \DB::table('openprize_opt_log')->insert($logs);

        $info=DB::table('lottery')
            ->find($lotteryId);
        $sta = $result === false ? 0 : 1;
        (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '自开奖管理', '自开奖管理', '设置开奖', $sta, "彩种名称:{$info->name}".$str);
    }
};
