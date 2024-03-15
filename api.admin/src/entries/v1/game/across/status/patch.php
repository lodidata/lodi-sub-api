<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

/**
 * 编辑橫版游戏开关
 *
 */
return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {

        $params = $this->request->getParams();
        $this->checkID($params['id']);

        if(!isset($params['type']) ||!isset($params['across_status'])){
            return $this->lang->set(886,['type或across_status参数不能为空']);
        }

        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        if($params['type']=='p' || $params['type']=='c'){
            $th = \Model\Admin\GameMenu::find($params['id']);
        }else{
            $th = \Model\Admin\Game3th::find($params['id']);
        }
        
        $th->across_status = $params['across_status'];
        $th->desc_desc='横排游戏开关:';
        $res = $th->save();
        if ($res !== false) {
            //删除缓存
            \Model\GameMenu::delMenuRedis();
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
