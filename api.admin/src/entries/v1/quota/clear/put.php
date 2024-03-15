<?php
/**
 * 额度信息展示
 * @author Taylor 2019-01-21
 */

use Logic\Admin\BaseController;

return new class extends BaseController
{

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        $clear_start_date=$this->request->getParam('clear_start_date','');
        $clear_end_date=$this->request->getParam('clear_end_date','');
        if(!$clear_start_date || !$clear_end_date){
            return $this->lang->set(886, ['日期不能为空']);
        }
        DB::table('rpt_order_amount')
            ->havingRaw("SUM(game_bet_amount)-SUM(game_prize_amount)>0")
            ->whereBetween('count_date', [$clear_start_date, $clear_end_date])
            ->update(['clear_status' => 1]);
        return true;
    }
};