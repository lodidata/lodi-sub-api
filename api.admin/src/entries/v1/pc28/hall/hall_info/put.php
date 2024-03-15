<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '厅设置列表';
    const DESCRIPTION = '接口';


    const QUERY       = [
    ];
    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
           'verifyToken', 'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $roomData = $params['room'];
        if($roomData){
            foreach ($roomData as $k=>$v){
                $v = (array)$v;
                $roomName = $v['room_name'];
                $roomNumber = isset($v['number']) ? $v['number'] : NULL;
                $sql = "update room set room_name = '$roomName' ";
                if($roomNumber !== NULL){
                    $sql .=",number = $roomNumber ";
                }
                $sql .=  "  where id = {$v['id']}";
                DB::update($sql);
            }
        }
        $sql = $this->getSql($params??[]);
        DB::update($sql);
        //更改同类彩票的回水条件和回水规则
        $flag = false;
        $set = "";
        if(isset($params['rebet_condition']) && $params['rebet_condition']){
            $rebet_condition  = $params['rebet_condition'];
            $set .=",rebet_condition = '{$rebet_condition}' ";
        }
        if(isset($params['rebet_config']) && $params['rebet_config']){
            $rebet_config  = $params['rebet_config'];
            $set .=",rebet_config = '{$rebet_config}' ";
        }
        if(isset($params['min_balance']) && $params['min_balance']){
            $min_balance  = $params['min_balance'];
            $set .=",min_balance = '{$min_balance}' ";
        }
        $set = trim($set,',');
        $type = $params['type'];
        $hall_level = $params['hall_level'];
        $sql = "update hall set $set where type = '$type' and hall_level = $hall_level";
        DB::update($sql);
        return [];

    }

    public function  getSql($data){
        foreach ($data as $k=>$v){
            $this->$k = $v;
        }
        $plus = '';


        if (isset($this->hall_name)) {
            $plus .= "hall_name = '{$this->hall_name}',";
        }
        if (isset($this->rebet_config)) {
            $plus .= "rebet_config = '{$this->rebet_config}',";
        }
        if (isset($this->rebet_desc)) {
            $plus .= "rebet_desc='{$this->rebet_desc}',";
        }
        if (isset($this->min_bet)) {
            $plus .= "min_bet='{$this->min_bet}',";
        }
        if (isset($this->max_bet)) {
            $plus .= "max_bet='{$this->max_bet}',";
        }

        if (isset($this->min_balance)) {
            $plus .= "min_balance = '{$this->min_balance}',";
        }
        if (isset($this->rebot_min)) {
            $plus .= "rebot_min='{$this->rebot_min}',";
        }
        if (isset($this->rebot_max)) {
            $plus .= "rebot_max='{$this->rebot_max}',";
        }
        if (isset($this->per_max)) {
            $plus .= "per_max='{$this->per_max}',";
        }
        if (isset($this->rebot_list)) {
            $plus .= "rebot_list='{$this->rebot_list}',";
        }
        if (isset($this->rebet_condition)) {
            $plus .= "rebet_condition='{$this->rebet_condition}',";
        }
        $plus = strlen($plus) ? trim($plus, ',') : '';

        return "UPDATE `hall` SET  {$plus} WHERE id = {$this->id}";
    }

};
