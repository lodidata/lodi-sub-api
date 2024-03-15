<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */

use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\AdvertValidate;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{

    const TITLE = '修改PC轮播广告/H5轮播广告状态';
    const DESCRIPTION = '申请、停用、启用';
    const HINT = '状态：审核中、被拒绝、通过，停用、启用';
    const QUERY = [];
    
    const PARAMS = [
        'id'        => "int(required) #id",
        "name"      => "string(required) #标题",
        "position"  => "string #显示位置",
        'pf'        => "enum[pc,h5] #平台，可选值：pc h5",
        'type'      => "enum[float,banner] #广告类型 float站内活动， banner外部链接",
        'link'      => "string #链接地址",
        'sort'      => "int #排序 从大到小",
        "picture"   => "string #图片地址",
        "status"    => "enum[default,enabled,disabled] #状态 default 申请,enabled 启用,disabled 停用",
        'language_id' => "int #语言ID"
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {

        if (!$id || !is_numeric($id)) {

            (new AdvertValidate())->paramsCheck('put', $this->request, $this->response);//参数校验,新增
            return (new advertLogic($this->ci))->createAdvert($this->request->getParams());

        } else {

//            (new AdvertValidate())->paramsCheck('patch',$this->request,$this->response);//参数校验,修改
            $validate = new BaseValidate([
                'status' => 'require|in:enabled,disabled',
            ]);

            $validate->paramsCheck('', $this->request, $this->response);

            $params = $this->request->getParams();

            $advert = \Model\Admin\Advert::find($id);
            $advert->name           = $params['name'];
            $advert->status         = $params['status'];
            $advert->position       = $params['position'];
            $advert->link_type      = $params['link_type'];
            $advert->link           = $params['link']??'';
            $advert->sort           = $params['sort'];
            $advert->language_id    = $params['language_id'];
            if (!empty($params['picture'])) {
                $advert->picture = replaceImageUrl($params['picture']);
            }
            $res = $advert->save();

            if (!$res) {
                return $this->lang->set(-2);
            }
            $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 1);
            $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 2);
            return $this->lang->set(0, [], $res, '');
        }


    }
};