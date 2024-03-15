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
        $params=$this->request->getParams();
        if(!$params['clear_start_date'] || !$params['clear_end_date']){
            return $this->lang->set(886, ['日期不能为空']);
        }
        DB::table('rpt_order_amount')
            ->whereBetween('count_date', [$params['clear_start_date'], $params['clear_end_date']])
            ->update(['clear_status' => 1]);

        $res = DB::table('quota')
            ->where('cust_id', '=', $params['cust_id'])
            ->update(['use_quota' =>$params['use_quota'], 'surplus_quota' => $params['surplus_quota'], 'admin_user' =>  $params['admin_user'],]);
        echo true;
    }
};