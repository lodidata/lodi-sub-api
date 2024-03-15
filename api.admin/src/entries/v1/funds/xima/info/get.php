<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {

    const TITLE = '洗码记录';

    //前置方法
    protected $beforeActionList = [
       'verifyToken', 'authorize',
    ];

    public function run($id = '') {

        $this->checkID($id);

        $data = DB::table('xima_order_detail as od')
            ->leftJoin('game_menu as gm','od.game_type_id','=','gm.id')
            ->where('od.order_id',$id)
            ->selectRaw('od.id,gm.name,od.dml,tmp_percent,amount')
            ->get()->toArray();
        return $data;

    }
};
