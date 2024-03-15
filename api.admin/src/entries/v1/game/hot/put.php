<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\GameMenu;
use Utils\Utils;

/**
 * 编辑竖版热门游戏
 *
 */
return new class() extends BaseController
{
    const PARAMS      = [
        [
            'id'     => 'int(required) # id',
            'sort'   => 'int(required) # 排序',
            'is_hot' => 'int(required) # 1：热门，0：非热门',
        ]
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {

        $params = $this->request->getParsedBodyParam('data');

        if ($params) {
            $res=Utils::updateBatch($params,'game_3th');
            if ($res!==false) {
                $this->redis->del(\Logic\Define\CacheKey::$perfix['pageHotGameList'].date('Y-m-d',time()));
                (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '游戏设置', '竖排热门游戏设置', '编辑', 1, "");
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_MENU, '游戏设置', '竖排热门游戏设置', '编辑', 0, "");
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
