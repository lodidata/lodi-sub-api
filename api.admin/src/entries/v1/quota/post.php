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
        $param=$this->request->getParams();
        DB::table('quota')
            ->insert($param);

        echo true;
    }
};