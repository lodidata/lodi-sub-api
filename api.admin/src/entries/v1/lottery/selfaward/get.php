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
use lib\validate\BaseValidate;
use Logic\Admin\Lottery as lotteryLogic;
return new  class() extends BaseController
{
    const TITLE       = '自开奖开奖列表结果';
    const DESCRIPTION = '获取指定彩种的开奖结果信息，高频彩获取最近3天，低频彩最近3个月';
    const QUERY       = [
        'page'       => 'int()   #页码',
        'page_size'  => 'int()    #每页大小',
        'lottery_id' => 'int(required) #彩种ID，参见彩种列表接口，http://admin.las.me:8888/lottery/types?debug=1',
        'type'       => 'string(required) #彩种简称'
    ];
    const PARAMS      = [];
    const STATEs      = [
    ];
    const SCHEMAS     = [
        [
            'id' => 'int #ID',
            'lottery_number' => 'int #彩期期号',
            'period_code' => 'string #开奖号码',
            'period_type' => '集合(manual_lottery 手动填写开奖号码 interval_lottery 返奖开奖 rand_lottery 奖池开奖)',
            'start_time' => 'int #开盘时间',
            'end_time' => 'int #封盘时间',
            'pay_money' => 'int #投注金额',
            'send_money' => 'int #派奖金额',
            'period_count' => 'int #开奖结果数量',
            'prize_counts' => 'int #中奖结果数量',
            'state' => 'int #状态1已开售0未开售2已结束',
            'is_manual_lottery' => 'int #状态只针对未结束的    1手动开奖，2系统开奖 其它为0',
            'profit' => 'int #返奖率开奖时的返奖率',
            'max_prize_once_money' => 'int #单次最大派奖金额',
            'min_prize_once_money' => 'int #单次最小派奖金额',
            'prize_profit' => 'int #中奖率',
            'desc' => 'int #备注',
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        (new BaseValidate([
            'lottery_id'  => 'require|isPositiveInteger',
        ]))->paramsCheck('',$this->request,$this->response);

        $req     = $this->request->getParams();
        $lottery_number = \Model\LotteryInfo::getCacheCurrentPeriod($req['lottery_id'])['lottery_number'] ?? '';
        if(!$lottery_number){
            $current = $this->lottery_periods(intval($req['lottery_id']));
            $lottery_number = $current[0]['lottery_number'];
        }
        $from = strtotime('10 days ago');

        $params = [
            'select'    => '*',
            'page'      => $req['page'] ?? 1,
            'page_size' => $req['page_size'] ?? 20,
            'number_to'    => $lottery_number + 9 ,
            'date_from'    => $from ?? null

        ];
        $rs     = (new lotteryLogic($this->ci))->openresult(intval($req['lottery_id']),$req['type'],$params);

        if (!$rs) {
            return [];
        }
        $now = time();
        $data = $rs['data'];
        $tmp_jackpot_key = -1;
        $openprize_config = \DB::table('openprize_config')->where('lottery_id', '=', $req['lottery_id'])->first();
        foreach ($data as $key=>&$datum) {
            $datum['interval_start'] = $datum['interval_start'] * 100;
            $datum['interval_end'] = $datum['interval_end'] * 100;
            if($datum['start_time'] < $now && $now < $datum['end_time']+19){
                $datum['state'] = 1;
            }elseif($now > $datum['end_time']){
                $datum['state'] = 2;
            }else{
                $datum['state'] = 0;
            }
            $datum['is_manual_lottery'] = $datum['state'] !=2 && $datum['period_code'] ? 1 : ($datum['state'] !=2 ? 2 : 0);
            $datum['created'] = explode(" ",$datum['created'])[0];
            if($datum['state'] != 2){
                $datum['pay_money'] = '';
                $datum['send_money'] = '';
                $datum['profit'] = '';
                $datum['period_count'] = '';
                $datum['prize_counts'] = '';
                $datum['min_prize_once_money'] = '';
                $datum['max_prize_once_money'] = '';
                $datum['prize_profit'] = '';
                $datum['jackpot'] = '';
                $datum['desc'] = '';
                if($datum['period_code']){
                    $datum['period_type'] = 'manual_lottery';
                }else{
                    if($openprize_config) {
                        switch ($openprize_config->period_code_type){
                            case 'interval' : $datum['period_type'] = 'interval_lottery';break;
                            case 'jackpot' : $datum['period_type'] = 'jackpot_lottery';break;
                            default : $datum['period_type'] = 'rand_lottery';break;
                        }
                    }
                    $tmp_jackpot_key = $key;
                }

            }else {
                $tmp = (array)\DB::table('self_open')->where('lottery_id', '=', $req['lottery_id'])->where('lottery_number', '=', $datum['lottery_number'])->first();
                $datum['pay_money'] = $tmp['pay_money'] ?? 0;
                $datum['send_money'] = $tmp['send_money'] ?? 0;
                $datum['profit'] = $datum['pay_money'] ?  round($datum['send_money'] /$datum['pay_money'] * 100, 2) : 0;
                $datum['period_count'] = $tmp['counts'] ?? 0;
                $datum['prize_counts'] = $tmp['prize_counts'] ?? 0;
                $datum['min_prize_once_money'] = $tmp['min_prize_once_money'] ?? 0;
                $datum['max_prize_once_money'] = $tmp['max_prize_once_money'] ?? 0;
                $datum['prize_profit'] = $datum['period_count'] ? round($datum['prize_counts'] /$datum['period_count'] * 100, 2) : 0;
                $datum['jackpot'] = $tmp['jackpot'] ?? 0;
                $tmp['desc'] = isset($tmp['desc']) ? $tmp['desc'] : '';
                $datum['desc'] = $datum['period_type'] != 'rand_lottery' ? $tmp['desc'] : '';
            }
        }
        if($tmp_jackpot_key != -1 && isset($data[$tmp_jackpot_key])) {
            if($data[$tmp_jackpot_key]['period_type'] == 'jackpot_lottery')
                $data[$tmp_jackpot_key]['desc'] = $openprize_config->jackpot_set ? '重置' . ($openprize_config->jackpot / 100) : '';
            elseif($data[$tmp_jackpot_key]['period_type'] == 'interval_lottery')
                $data[$tmp_jackpot_key]['desc'] = ($openprize_config->min_profit*100).'%-'.($openprize_config->max_profit*100).'%';
            else
                $data[$tmp_jackpot_key]['desc'] = '';
        }
        return $this->lang->set(0,[],$data,$rs['attributes']);
    }


    /**
     * 彩票期号列表（从当前期倒序）
     *
     * @param int $id
     *            彩票ID
     * @param array $params
     *            ['page'=>1,'page_size'=>2,];
     */
    public function lottery_periods(int $id) {

        $cur = (new lotteryLogic($this->ci))->curperiod($id);
//        print_r($cur);exit;
        return (new lotteryLogic($this->ci))->periods($id, $cur[0]['lottery_number']);

    }

};
