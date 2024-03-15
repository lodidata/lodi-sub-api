<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\GameMenu;
use Utils\Utils;
use Model\Game3th;

return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = false, $type = 'p')
    {

        $params = $this->request->getParsedBodyParam('data');
        $type = $this->request->getParam('type', 'p');
//        $params=[["id"=>1,"sort"=>1],["id"=>4,"sort"=>3],["id"=>15,"sort"=>4],["id"=>21,"sort"=>4],["id"=>16,"sort"=>5],["id"=>22,"sort"=>6]];

        if ($params) {
            if ($type == 'p' || $type == 'c') {
                $table = 'game_menu';
                $game_3th_id = null;
            } else {
                $table       = 'game_3th';
                $game_3th_id = current($params)['id'];
            }
            $res=Utils::updateBatch($params,$table);

            if ($res!==false) {
                (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '菜单设置', '菜单排序设置', '编辑', 1, "");
                //删除缓存
                \Model\GameMenu::delVerticalMenuRedis($game_3th_id);
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '菜单设置', '菜单排序设置', '编辑', 0, "");
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
