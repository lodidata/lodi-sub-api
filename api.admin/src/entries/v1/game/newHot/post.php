<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\GameMenu;
use Utils\Utils;

/**
 * 编辑竖版热门游戏图标
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

        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        $params = $this->request->getParams();


        if (!isset($params['id']) || !$params['id']) {
            return $this->lang->set(886, ['id参数不能为空']);
        }

        if (!isset($params['type']) || !$params['type']) {
            return $this->lang->set(886, ['type参数不能为空']);
        }
        if (!isset($params['img']) || !$params['img']) {
            return $this->lang->set(886, ['game_img参数不能为空']);
        }

        $res = false;
        if ($params['type'] == 'p') {
            $id = 0;
            $res = DB::table('game_menu')
                ->where('id', '=', $params['id'])
                ->update(['img' => replaceImageUrl($params['img'])]);
        }

        if($res!==false){
            \Model\GameMenu::delVerticalMenuRedis($id);
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }

};
