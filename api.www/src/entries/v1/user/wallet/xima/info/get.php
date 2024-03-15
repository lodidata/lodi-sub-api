<?php
use Utils\Www\Action;
return new class extends Action {
    const TITLE = "某个注单的打码量";
    const TAGS = "打码量";
    const QUERY = [
        "id" => "int(required) #订单id"
    ];
    const SCHEMAS = [
        [
            "id" => "int #id",
            "name" => "string #游戏名称",
            "dml" => "int #打码量",
            "tmp_percent"=> "int #当时转化率",
            "amount"=> "int #转换金额"
        ]
    ];
    public function run($id = '') {

//        $this->checkID($id);

        $data = DB::table('xima_order_detail as od')
            ->leftJoin('game_menu as gm','od.game_type_id','=','gm.id')
            ->where('od.order_id',$id)
            ->selectRaw('od.id,gm.name,od.dml,tmp_percent,amount')
            ->get()->toArray();
        return $data;

    }
};