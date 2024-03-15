<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {

    const TITLE       = '删除轮播广告';
    const DESCRIPTION = 'PC轮播广告/H5轮播广告';

    const QUERY       = [
        'id' => 'int(required) #轮播广告id'
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null) {

        $this->checkID($id);
        $info=DB::table('advert')->find($id);
        $res=(new advertLogic($this->ci))->delAdvertById($id);

        $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 1);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 2);
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        $type=$info->pf=='pc'?'PC':'移动端';
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '轮播广告', $type, "删除", $sta, "广告名称：{$info->name}");
        /*============================================================*/
        return $res;

    }
};