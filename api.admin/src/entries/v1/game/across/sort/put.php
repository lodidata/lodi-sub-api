<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\GameMenu;
use Utils\Utils;

return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = false)
    {

        $data = $this->request->getParsedBodyParam('data');
        $type = $this->request->getParam('type', 'p');
//        $params=[["id"=>10,"across_sort"=>1,"is_hot"=>1],["id"=>11,"across_sort"=>3,"is_hot"=>1]];

        if ($data) {
            if ($type == 'p' || $type == 'c') {
                $table = 'game_menu';
            } else {
                $table = 'game_3th';
            }
            $res = Utils::updateBatch($data, $table);
            if ($res !== false) {
                (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '菜单设置', '横排菜单排序设置', '编辑', 1, "");
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '菜单设置', '横排菜单排序设置', '编辑', 0, "");
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
