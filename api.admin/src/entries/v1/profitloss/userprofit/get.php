<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        $res=(array)DB::table("user_agent")
                      ->where("user_id",$id)
                      ->get('profit_loss_value')[0];
        return $res;

    }


};
