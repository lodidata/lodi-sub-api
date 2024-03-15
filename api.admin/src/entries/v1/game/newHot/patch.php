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
        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        if($params['type']=='c' || $params['type']=='p'){
            if(!isset($params['alias'])){
                return $this->lang->set(886,['alias参数不能为空']);
            }
            $res = \Model\Admin\GameMenu::where('alias', $params['alias'])->update(['hot_status'=>$params['status']]);
            $id = null;
        }else{
            $this->checkID($params['id']);
            $id = $params['id'];
            $th = \Model\Admin\Game3th::find($params['id']);
            $th->hot_status = $params['status'];
            $th->desc_desc='热门游戏开关:';
            $res = $th->save();
        }

        if ($res !== false) {
            \Model\GameMenu::delVerticalMenuRedis($id);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
