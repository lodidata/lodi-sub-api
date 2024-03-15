<?php

use Logic\Admin\Spread as SpreadLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController {

    const TITLE = '删除推广引导图片';

    const DESCRIPTION = '推广引导图片删除';

    

    const QUERY = [
        'id' => 'int(required) #推广广告id',
    ];



    const PARAMS = [];

    const SCHEMAS = [

    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null) {
        $this->checkID($id);

        $this->redis->del(\Logic\Define\CacheKey::$perfix['spreadPicList']);
        $logic = new SpreadLogic($this->ci);

        return $logic->remove($id);
    }
};