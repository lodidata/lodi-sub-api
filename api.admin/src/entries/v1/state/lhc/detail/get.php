<?php
/**
 * 六合彩彩期，统计特码对应每个号码的下注金额
 */

use Logic\Admin\BaseController;

return new class() extends BaseController {
    private $reportDB;

    public function run() {
        $lottery_number = $this->request->getParam('lottery_number');//彩期
        if(empty($lottery_number)){
            return $this->lang->set(886, ['彩期不能为空']);
        }

        $this->reportDB =  \DB::connection('default');

        $r = range(1, 49);
        $data = $this->reportDB->table('rpt_order_num')->where('lottery_number', $lottery_number)->pluck('lottery_bet_amount','lottery_num');
        if(!empty($data)){
            $data = $data->toArray();
            $is_in = true;
        }else{
            $is_in = false;
        }
        foreach ($r as $val){
            $tmp = $val < 10 ? '0'.$val : $val;
            $res[$val] = $is_in ? ($data[$tmp] ?? 0) : 0;
        }
        return $this->lang->set(0, [], $res);
    }
};
