<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

/**
 * 编辑游戏名称
 *
 */
return new class() extends BaseController
{

    const TITLE = "编辑游戏名称";
    const QUERY = [

    ];
    const PARAMS = [
        'id'        => 'int(required) #id',
        'type'      => 'enum[p,c,cs](required) #类型：p：父菜单；c：二级菜单；cs:三級菜单',
        'name'      => 'string(required) #泰文游戏名称',
        'rename'    => 'string(required) #中文游戏名称',
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {

        $params = $this->request->getParams();
        $this->checkID($params['id']);

        if(!isset($params['type']) ||!isset($params['name'])){
            return $this->lang->set(886,['type或name参数不能为空']);
        }

        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        if($params['type']=='p' || $params['type']=='c'){
            $th = \Model\Admin\GameMenu::find($params['id']);
            $th->name = $params['name'];
        }else{
            $th = \Model\Admin\Game3th::find($params['id']);
            $th->game_name = $params['name'];
        }

        $th->rename = $params['rename'];
        $res = $th->save();
        if ($res !== false) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
