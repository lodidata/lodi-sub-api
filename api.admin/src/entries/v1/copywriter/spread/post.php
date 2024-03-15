<?php

/**
 * 推广页图片配置
 *
 * @author Jacky.Zhuo<zhuojiejie@funtsui.com>
 * @date 2017-07-09 11:21
 */

use Logic\Admin\Spread as SpreadLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\SpreadValidate;

return new class() extends BaseController {

    const TITLE = '新增推广页图片';

    const DESCRIPTION = '新增推广页图片，在H5端用户通过推广链接进入后显示';



    const QUERY = [];



    const PARAMS = [
        'name'    => 'string(required) #推广页图片名称',
        'sort'    => 'int(required) #排序',
        'picture' => 'string(required) #图片地址',
        'status'  => 'enum[disabled,enabled,deleted](required) #停用:disabled 启用:enabled 删除:deleted',
    ];

    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $validate = new SpreadValidate();

        $validate->paramsCheck('post', $this->request, $this->response);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['spreadPicList']);
        $loginSpread = new SpreadLogic($this->ci);

        return $loginSpread->create($this->request->getParams());
    }

};