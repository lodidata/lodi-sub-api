<?php

use \Logic\Admin\BaseController;
use Logic\Admin\Log;

return new  class() extends BaseController{
    const TITLE = '盈亏返佣 盈亏成本设置';
    const DESCRIPTION = '';

    const QUERY = [
    ];

    const PARAMS = [
        'name'             => 'string() #项目名',
        'type'             => 'int() #占比类型 1:游戏盈亏,2:充值,3:取款，4:营收, 5:平台彩金, 6:平台服务(人工扣款)',
        'proportion_value' => '占比值',
        'settle_status'    => 'int() #参与结算 1:是,2:否',
        'part_value'       => '参与比例值',
        'status'           => '状态,1:正常,2:停用',

    ];
    const SCHEMAS = [
        [
        ],
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];


    public function run($id=null){
        $param=$this->request->getParams();
        unset($param['s']);

        $v = [
            'name' => 'require',
            'proportion_value' => 'require',
            'part_value' => 'require',
            'type' => 'require',
            'settle_status' => 'require',
        ];

        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);

        if($param['proportion_value'] > 100 || $param['part_value'] > 100){
            return $this->lang->set(886, ['比例不能超过100']);
        }

        if($id){
            $type = '修改';
            $checkSql=DB::table("agent_loseearn_fee")->where('type',$param['type'])->where('id','!=',$id);

            $check=$checkSql->first();

            if($check){
                return $this->lang->set(886, ['该类型已经存在']);
            }
            $res=DB::table("agent_loseearn_fee")->where('id',$id)->update($param);
        }else{
            $type = '新增';
            $checkSql=DB::table("agent_loseearn_fee")->where('type',$param['type']);

            $check=$checkSql->first();
            if($check){
                return $this->lang->set(886, ['该类型已经存在']);
            }
            $res=DB::table("agent_loseearn_fee")->insert($param);
        }

        if(!$res){
            return $this->lang->set(-2);
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_SYSTEM, '返佣结算', '盈亏成本', $type, 1, json_encode($param));
        return $this->lang->set(0);
    }
};
