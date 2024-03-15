<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

/**
 * 编辑竖版游戏开关
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

        if(!isset($params['type']) ||!isset($params['status'])){
            return $this->lang->set(886,['type或status参数不能为空']);
        }
        $this->checkID($params['id']);
        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        if($params['type']=='p' || $params['type']=='c'){
            $id = 0;
            $th = \Model\Admin\GameMenu::find($params['id']);
        }else{
            $id = $params['id'];
            $th = \Model\Admin\Game3th::find($params['id']);
        }
        $th->status = $params['status'];
        $th->desc_desc='竖版游戏开关:';
        $res = $th->save();
        if ($res !== false) {
            \Model\GameMenu::delVerticalMenuRedis($id);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
