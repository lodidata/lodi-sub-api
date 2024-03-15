<?php
return new class() extends \Logic\Admin\BaseController
{
    const TITLE = '系统维护';
    const DESCRIPTION = '系统维护';
    const QUERY = [
        'maintaining' => 'string #等级名称',
    ];
    const SCHEMAS = [

    ];

    //前置方法
   protected $beforeActionList = [
       'verifyToken', 'authorize'
   ];

    public function run()
    {
        $maintaining = $this->request->getParam('maintaining');
        $maintaining = intval($maintaining) ? 1 : 0;
        \DB::table('system_config')
            ->where('module', 'system')
            ->where('key', 'maintaining')
            ->update(['value' => $maintaining]);
        $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
        echo 1;
        die();
    }

};
