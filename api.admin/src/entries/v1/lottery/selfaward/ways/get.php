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
return new class() extends BaseController
{
    const STATE       = "";
    const TITLE       = '自开奖开奖概率控制';
    const DESCRIPTION = '';
    
    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const STATEs      = [
    ];
    const SCHEMAS     = [
        [
            "jackpot"=> '0#奖池金额',
            "interval_profit"=> "100.00#返奖控制率",
            "max_profit"=> "0.90#最大返奖率",
            "min_profit"=>"0.30#最小返奖率",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $req     =$this->request->getParams();
        $lotteryId = $req['lottery_id'];
        $data = \DB::table('openprize_config')->select(['max_profit','min_profit','jackpot','interval_profit'])->where('lottery_id',$lotteryId)->get()->toArray();
        if($data){
            $data[0]->jackpot = 0;
        }
        return $data;
    }
};
