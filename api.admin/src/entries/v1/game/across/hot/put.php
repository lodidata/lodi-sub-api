<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Utils\Utils;

/**
 * 编辑橫版热门游戏
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

        $params = $this->request->getParsedBodyParam('data');
//        $params=[["id"=>10,"across_sort"=>1,"is_hot"=>1],["id"=>11,"across_sort"=>3,"is_hot"=>1]];

        if ($params) {
            $res=Utils::updateBatch($params,'game_3th');
            if ($res!==false) {
                (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '游戏设置', '横排热门游戏设置', '编辑', 1, "");
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '游戏设置', '横排热门游戏设置', '编辑', 0, "");
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
