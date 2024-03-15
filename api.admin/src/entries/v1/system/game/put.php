<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新首页全部游戏排序';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data = $this->request->getParams();
        unset($data['s']);
        $tmp['game'] = \Logic\Set\SystemConfig::getModuleSystemConfig('game');
        $confg = new \Logic\Set\SystemConfig($this->ci);

        $confg->updateSystemConfig($data,$tmp);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['allGameList']);
        return $this->lang->set(0);
    }
};
