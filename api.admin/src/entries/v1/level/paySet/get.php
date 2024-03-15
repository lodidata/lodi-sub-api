<?php
/**
 * 获取层级支付列表
 * @author Taylor 2019-01-12
 */
use Logic\Level\Level as Level;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE   = '获取层级支付列表';
    const PARAMS  = [];
    const SCHEMAS = [
        [
            "online" => 'string #线上支付列表',
            "offline"=> 'string #线下支付列表',
            "level"=> 'int #下一个层级',
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $id = $this->request->getParam('id', 1);
        $levelLogic = new Level($this->ci);
        $offLine = $levelLogic->getOfflineSet($id);
        $onLine = $levelLogic->getOnlineSet($id);
        $level_value = DB::table('user_level')->count('id');
        return ['online'=>$onLine,'offline'=>$offLine,'level'=>$level_value+1];
    }
};
