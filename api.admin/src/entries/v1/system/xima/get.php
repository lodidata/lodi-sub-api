<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '洗码设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $game_type_id = $this->request->getQueryParam('game_type_id','');

        $res = DB::table('xima_config')->where('game_type_id',$game_type_id)->get()->toArray();
        return $res;
    }
};
