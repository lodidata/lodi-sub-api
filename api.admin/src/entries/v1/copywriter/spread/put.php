<?php

use Logic\Admin\BaseController;
use Logic\Admin\Spread as SpreadLogic;
use lib\validate\admin\SpreadValidate;

return new class() extends BaseController {

    const TITLE = '修改推广引导图片';

    const DESCRIPTION = '修改推广引导图片信息';



    const QUERY = [];



    const PARAMS = [
        'id' => 'int(required) #推广广告id',
        'name'    => 'string(required) #推广页图片名称',
        'sort'    => 'int(required) #排序',
        'picture' => 'string(required) #图片地址',
        'status'  => 'string(required) #停用:disabled 启用:enabled 删除:deleted',
    ];

    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null) {
        $validate = new SpreadValidate();

        $validate->paramsCheck('post', $this->request, $this->response);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['spreadPicList']);
        $loginSpread = new SpreadLogic($this->ci);

        return $loginSpread->update($id, $this->request->getParams());
    }
};