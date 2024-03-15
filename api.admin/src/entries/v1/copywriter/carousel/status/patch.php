<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */

use Logic\Admin\Log;
use Utils\Www\Action;
use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Respect\Validation\Validator as v;
return new class() extends BaseController {

    const TITLE       = '修改PC轮播广告/H5轮播广告状态';
    const DESCRIPTION = '申请、停用、启用';
    const HINT        = '状态：审核中、被拒绝、通过，停用、启用';
    const QUERY       = [];
    
    const PARAMS      = [
        'status'        => 'enum[applying,disabled,enabled](required) #申请 applying，停用 disabled，启用 enabled',
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null) {

        $this->checkID($id);
        
        $validate = new BaseValidate([
            'status'  => 'require|in:enabled,disabled',
        ]);

        $validate->paramsCheck('',$this->request,$this->response);


        $advert = \Model\Admin\Advert::find($id);
        $advert->status = $this->request->getParam('status');
        $advert->logs_type='开启/关闭';
        $res = $advert->save();
        if (!$res) {
            return $this->lang->set(-2);
        }
        $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 1);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 2);
        return $this->lang->set(0, [], $res, '');
    }
};