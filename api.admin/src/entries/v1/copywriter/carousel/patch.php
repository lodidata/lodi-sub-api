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
return new class() extends BaseController {

    const TITLE       = '修改PC轮播广告/H5轮播广告状态';
    const DESCRIPTION = '申请、停用、启用';
    const HINT        = '状态：审核中、被拒绝、通过，停用、启用';
    const QUERY       = [];
    
    const PARAMS      = [
        'language_id' => 'int(required) #语言id',
        'pf'          => 'enum[pc,h5](required,h5) #平台（pc, h5）',
        'position'    => 'enum[home,egame,live,lottery,sport,agent](required) #用于展示哪个位置(pc使用)，可选值，home,egame,live,lottery,sport,agent'
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null) {

        $this->checkID($id);
        $params = $this->request->getParams();

        $state = [
            'language_id' => $params['language_id'],
            'pf'          => $params['pf'],
            'position'    => $params['position'],
            'type'        => 'banner'
        ];

        if (!in_array($params['type'], ['applying', 'disabled', 'enabled'], true)) {

            return createRsponse($this->response,200,10,'The type of param `type` is invalid');
        }
        if ($params['type'] == 'applying') {
            $state['approve'] = "applying";
        } else {
            $state['status'] = $params['type'];
        }

        if($result = $this->update($id,$state)){
            if($params['type'] == 'applying') {
//                $origin = SELECT id,`name`,pf,position,link_type,link,type,sort,language_id,picture,language_name,status,created,approve FROM `[advert]` WHERE id=$this->id;
                $origin = DB::table('advert')
                    ->select(DB::raw("id,`name`,pf,position,link_type,link,type,sort,language_id,picture,language_name,status,created,approve"))->find($id);

            }
            $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 1);
            $this->redis->del(\Logic\Define\CacheKey::$perfix['banner'] . 2);
            return $this->lang->set(0);
        }else{
            return $this->lang->set(-2);
        }


    }

    protected function update(int $id, array $advert){

        if (isset($advert['approve']) && $advert['approve'] != 'pending' && $advert['approve'] != 'applying') {
            unset($advert['approve']);
        }

        $rs = DB::table('advert')->where('id',$id)->update($advert);

        if ($rs && isset($advert['type'], $advert['language_id'], $advert['pf'], $advert['position'], $advert['status']) && $advert['status'] == 'enabled') {
            DB::table('advert')
                ->where('status','enabled')
                ->where('pf',$advert['pf'])
                ->where('type',$advert['type'])
                ->where('position',$advert['position'])
                ->where('id','<>',$id)
                ->update(['status'=>'disabled']);

        }

        return $rs;
    }
    /**
     * 设置/获取 交互数据
     *
     * @param array|null $data
     * @param string     $process 交互处理方法。 特殊情况：表: [table], 服务包：{module.service.mthod} 或 {service.method}, 默认为common module
     * @return array|mixed
     */
    function interactData(array $data = null, string $process = null) {
        static $_data;
        if ($data) {
            $_data = ['data' => $data, 'process' => $process, 'to' => 'p'];
        } else {
            return $_data;
        }

        return null;
    }
};