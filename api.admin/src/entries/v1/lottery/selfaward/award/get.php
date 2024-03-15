<?php
/**
 * Created by PhpStorm.
 * User: liluming
 * Date: 2017/11/2
 * Time: 15:08
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Lottery\Settle;
return new class() extends BaseController
{
    const TITLE = '手动开奖号码检索';
    const DESCRIPTION = '修改自开板块可输入号码开奖';
    
    const QUERY = [
        'lottery_id' => 'int(required) #自开型彩种ID',
        'number_id' => 'int() #彩期ID',
        'type' => 'string() #彩种类型',
        'codes' => 'string() #开奖号码',
    ];
    const SCHEMAS = [
        [
            'totalTou'             => 'int #当前累计投注',
            'totalWin'      => 'int #开奖后总派奖',
            'totalIncome'      => 'int #派奖后盈亏',
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
            ['lottery_id','require|isPositiveInteger'],
            ['number_id','require|isPositiveInteger'],
//            ['pid','require|isPositiveInteger'],
            ['codes','require'],
        ],
            [],
            ['lottery_id'=>'彩票ID']
            ))->paramsCheck('',$this->request,$this->response);
        try {
            $params = $this->request->getParams();
            $data['lottery_type'] = $params['lottery_id'];
            $data['lottery_number'] = $params['number_id'];
            $data['pid'] = DB::table('lottery')->where('id', $params['lottery_id'])->value('pid');
            $codes = str_replace('[','', $params['codes']);
            $codes = str_replace(']','', $codes);
            $codes = str_replace('"','', $codes);

            $data['period_code'] = $codes;
            $orderData = (new Settle($this->ci))->calOrder($data);
            ob_clean();  //清除输出缓存
            if ($orderData === false)
                return $this->lang->set(10572);
        }catch (\Exception $e){
            return $this->lang->set(10572);
        }
        return $orderData;
    }



};