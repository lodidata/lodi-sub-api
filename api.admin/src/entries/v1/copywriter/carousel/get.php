<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use Respect\Validation\Validator as v;

return new class() extends BaseController {

    const STATE       = 1;
    const TITLE       = '轮播广告列表';
    const DESCRIPTION = 'PC轮播广告/H5轮播广告 [home 首页,egame 电子页,live 视讯页,lottery 彩票页,sport 体育页,coupon 优惠页,agent 代理页]';
    
    const QUERY       = [
        'id'        => 'int() #当获取单个的时候传入',
        'pf'        => 'enum[pc,h5] #平台，可选值：pc h5',
        'page'      => 'int #第几页',
        'page_size' => 'int #每页多少条'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
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
        ]
    ];
    
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null ) {

//        $this->checkID($id);

        $params = $this->request->getParams();

        if (empty($id) || !is_numeric($id)) {
            if (isset($params['id']) && is_numeric($params['id'])) {
                $id = intval($params['id']);
            }
        }
        $validation = $this->validator->validate($this->request, [
            'pf' => v::notEmpty()->noWhitespace()->in(['pc','h5'])->setName('平台'),
        ]);
        if (!$validation->isValid()) {
            return $validation;
        }
        
        $data = [
            'page'      =>$params['page'] ,
            'page_size' => $params['page_size'],
            'pf'        => $params['pf'],
            'id'        => is_numeric($id) ? $id : null,
            'language_id'=> isset($params['language_id']) && is_numeric($params['language_id']) ?? null,
        ];

        return (new advertLogic($this->ci))->getAdvert($data);
       
    }
};