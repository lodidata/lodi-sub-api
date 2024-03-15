<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\Active as activeLogic;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController {

    const TITLE       = '给优惠活动申请写备注';
    const QUERY       = [
        'id' => 'int #申请id'
    ];

    const PARAMS      = [
        'content' => 'string #备注详情'
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id) {

        $this->checkID($id);
        
        $validate = new BaseValidate([
            'content'  => 'require|max:500',
        ]);

        $validate->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
        $active = DB::table('active_apply')->find($id);
        if(!$active){
            return $this->lang->set(10015);
        }

        $res = DB::table('active_apply')->where('id',$id)->update(['memo'=>$params['content']]);

        if($res === false){
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }
};