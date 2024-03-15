<?php

use \Logic\Admin\BaseController;
use Logic\Admin\Log;

/**
 * 新增盈利结算
 */

return new  class() extends BaseController{

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];


    public function run($id=null){

        $param=$this->request->getParams();
        unset($param['s']);

        $v = [
            'name' => 'require',
            'proportion_value' => 'require',
            'part_value' => 'require',
        ];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);

        if($param['proportion_value'] > 100 || $param['part_value'] > 100){
            return $this->lang->set(886, ['比例不能超过100']);
        }

        if($id){
            $checkSql=DB::table("user_agent_settle")->where('type',$param['type'])->where('id','!=',$id);
            if($param['type'] == 1){
                $checkSql->where('game_type',$param['game_type']);
            }
            $check=$checkSql->first();
            if($check){
                return $this->lang->set(886, ['该类型已经存在']);
            }
            $res=DB::table("user_agent_settle")->where('id',$id)->update($param);
        }else{

            $checkSql=DB::table("user_agent_settle")->where('type',$param['type']);

            if($param['type'] == 1){
                $checkSql->where('game_type',$param['game_type']);
            }
            $check=$checkSql->first();
            if($check){
                return $this->lang->set(886, ['该类型已经存在']);
            }
            $res=DB::table("user_agent_settle")->insert($param);
        }

        if(!$res){
            return $this->lang->set(-2);
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, '盈利结算', '盈利结算', '新增/修改', 1, json_encode($param));
        return $this->lang->set(0);
    }
};
