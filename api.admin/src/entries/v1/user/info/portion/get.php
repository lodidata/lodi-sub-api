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
                      ->selectRaw('proportion_type,proportion_value')
                      ->first();
        if (empty($res['proportion_type'])) {
            return [];
        }
        return $res;

    }


};
