<?php
/**
 * 额度信息修改
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
        $param = $this->request->getParams();

        $data = DB::table('quota')
            ->where('cust_id', '=', $param['cust_id'])
            ->get()
            ->first();
        if ($data) {
            DB::table('quota')
                ->where('cust_id', '=', $param['cust_id'])
                ->update($param);
        } else {
            DB::table('quota')
                ->insert($param);
        }


        echo true;
    }
};