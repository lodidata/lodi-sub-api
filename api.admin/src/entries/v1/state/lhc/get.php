<?php
/**
 * 六合彩彩期列表，统计特码金额
 */

use Logic\Admin\BaseController;

return new class() extends BaseController {
    private $reportDB;

    public function run() {
        $page = $this->request->getParam('page', 1);
        $size= $this->request->getParam('page_size', 20);
//        $size = 30;
        $lottery_number = $this->request->getParam('lottery_number');//彩期

        $query = \DB::table('lottery_info')->where('lottery_type',52);

        $lottery_number && $query->where('lottery_number', '=', $lottery_number);
        $sum = clone $query;
        $total = $sum->count();
        $res = $query->orderBy('lottery_number','DESC')->forPage($page, $size)
            ->get(['lottery_number', 'lottery_name', 'official_time'])->toArray();

        $this->reportDB =  \DB::connection('default');

        if(!empty($res)){
            foreach ($res as &$val){
                $val->bet_amount = $this->reportDB->table('rpt_order_num')->where('lottery_number', $val->lottery_number)->sum('lottery_bet_amount');
                $val->official_time = $val->official_time ? date('Y-m-d H:i:s', $val->official_time) : '';
            }
        }

        $last_time = $this->reportDB->table('rpt_order_num')->orderBy('updated', 'desc')->value('updated');

        $attributes['last_time'] = $last_time ? $last_time : '';//最近更新时间
        $attributes['count'] = $total;
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        return $this->lang->set(0, [], $res, $attributes);
    }
};
