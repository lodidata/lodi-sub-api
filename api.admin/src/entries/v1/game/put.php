<?php

use Logic\Admin\BaseController;

/**
 * 获取当前菜单的排序信息
 *
 *  接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
 *
 */
return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id)
    {
        $type = $this->request->getParam('type','p');
        $name = $this->request->getParam('name','');
        if (empty($name))  return $this->lang->set(-2);
            //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        if($type=='p' || $type=='c'){
            $th = \Model\Admin\GameMenu::find($id);
            $id = 0;
            $th->name = $name;
        }else{
            $th = \Model\Admin\Game3th::find($id);
            $th->game_name = $name;
        }

        $res = $th->save();
        if ($res !== false) {
            \Model\GameMenu::delVerticalMenuRedis($id);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
